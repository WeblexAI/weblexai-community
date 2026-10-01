import type { TranslationAPIResponseI, TranslationRequestItemI, TranslationResponseItemI } from '../types';
import { PageTranslationCache } from './PageTranslationCache';

const MAX_ITEMS = 100;
const MAX_BATCH_CHARS = 20_000;
const REQUEST_TIMEOUT = 100_000;

export class TranslationManager {
    private apiKey: string | null = null;
    private pageCache = new PageTranslationCache();
    private controllers = new Set<AbortController>();
    private requestEpoch = 0;

    constructor(private readonly translationEndpoint: string) {
        if (!translationEndpoint) throw new Error('A translation API endpoint is required.');
    }

    setApiKey(key: string): void {
        this.apiKey = key;
        this.pageCache.setNamespace(this.hashValue(`${this.translationEndpoint}:${key}`));
    }
    setCacheRevision(revision?: number): void {
        this.pageCache.setRevision(revision);
    }
    clearCache(): void {
        this.pageCache.clearPage();
    }
    getCacheStats() {
        return this.pageCache.getStats();
    }
    cancelActiveRequest(): void {
        this.requestEpoch += 1;
        this.controllers.forEach((controller) => controller.abort());
        this.controllers.clear();
    }

    async translateBatch(items: TranslationRequestItemI[], sourceLang: string, targetLang: string, onBatch?: (translations: TranslationResponseItemI[]) => void): Promise<TranslationResponseItemI[]> {
        if (!this.apiKey) return [];
        const epoch = this.requestEpoch;
        const valid = items.filter((item) => Number.isInteger(item.id) && item.text.trim() && [...item.text].length <= 10_000);
        const representatives = new Map<string, TranslationRequestItemI>();
        const idsByIdentity = new Map<string, number[]>();
        valid.forEach((item) => {
            const identity = this.identity(item);
            representatives.set(identity, representatives.get(identity) ?? item);
            idsByIdentity.set(identity, [...(idsByIdentity.get(identity) ?? []), item.id]);
        });
        const cached: TranslationResponseItemI[] = [];
        const missing: TranslationRequestItemI[] = [];
        representatives.forEach((item, identity) => {
            const translation = this.pageCache.get(identity, targetLang);
            if (translation === null) {
                missing.push(item);
                return;
            }
            (idsByIdentity.get(identity) ?? []).forEach((id) => cached.push({ ...item, id, translated: translation }));
        });
        if (epoch === this.requestEpoch) onBatch?.(cached);
        const results = [...cached];
        const batches = this.splitBatches(missing);
        let next = 0;
        const worker = async (): Promise<void> => {
            while (next < batches.length) {
                if (epoch !== this.requestEpoch) throw new DOMException('Translation request was cancelled.', 'AbortError');
                const batch = batches[next++];
                const received = await this.fetchWithStreaming(batch, sourceLang, targetLang, epoch, (translations) => {
                    if (epoch !== this.requestEpoch) return;
                    const expanded = this.expandAndCache(translations, idsByIdentity, targetLang);
                    results.push(...expanded);
                    onBatch?.(expanded);
                });
                if (!received) throw new Error('Translation stream ended before completion.');
            }
        };
        await Promise.all(Array.from({ length: Math.min(2, batches.length) }, worker));
        return results;
    }

    private expandAndCache(translations: TranslationResponseItemI[], idsByIdentity: Map<string, number[]>, targetLang: string): TranslationResponseItemI[] {
        const expanded: TranslationResponseItemI[] = [];
        translations.forEach((translation) => {
            const identity = this.identity(translation);
            this.pageCache.set(identity, targetLang, translation.translated);
            (idsByIdentity.get(identity) ?? []).forEach((id) => expanded.push({ ...translation, id }));
        });
        return expanded;
    }

    private async fetchWithStreaming(items: TranslationRequestItemI[], source: string, target: string, epoch: number, onBatch: (translations: TranslationResponseItemI[]) => void): Promise<boolean> {
        const controller = new AbortController();
        this.controllers.add(controller);
        const timeout = setTimeout(() => controller.abort(), REQUEST_TIMEOUT);
        const expected = new Map(items.map((item) => [item.id, item]));
        const received = new Set<number>();
        const validate = (translations: unknown[]): TranslationResponseItemI[] =>
            translations.map((value) => {
                if (!value || typeof value !== 'object') throw new Error('Malformed translation response.');
                const item = value as TranslationResponseItemI;
                const request = expected.get(Number(item.id));
                if (!request || received.has(Number(item.id)) || typeof item.translated !== 'string') throw new Error('Unexpected translation response.');
                if ((item.type ?? request.type ?? 'text') !== (request.type ?? 'text') || (item.attr ?? '') !== (request.attr ?? '') || (item.context ?? '') !== (request.context ?? ''))
                    throw new Error('Translation response identity mismatch.');
                received.add(Number(item.id));
                return { ...request, ...item, id: Number(item.id) };
            });
        try {
            const response = await fetch(`${this.translationEndpoint.replace(/\/$/, '')}/translations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${this.apiKey}`,
                    'X-Page-Url': encodeURIComponent(window.location.href),
                    'X-Page-Title': encodeURIComponent(document.title),
                },
                body: JSON.stringify({ source, target, translatables: items }),
                signal: controller.signal,
            });
            if (epoch !== this.requestEpoch) throw new DOMException('Translation request was cancelled.', 'AbortError');
            if (!response.ok) throw new Error(`Translation API error [${response.status}]: ${await response.text().catch(() => '')}`);
            if (!response.body) {
                const json = (await response.json()) as TranslationAPIResponseI;
                const rows = validate(json?.data?.translations ?? []);
                if (epoch === this.requestEpoch) onBatch(rows);
                if (received.size !== expected.size) throw new Error('Incomplete translation response.');
                return true;
            }
            const completion = await this.processNdjsonStream(response.body, (rows) => {
                if (epoch === this.requestEpoch) onBatch(validate(rows));
            });
            const withheldIds = new Set(completion.withheldIds);
            if (withheldIds.size !== completion.withheldIds.length || [...withheldIds].some((id) => !expected.has(id) || received.has(id))) throw new Error('Invalid withheld translation response.');
            if (!completion.complete || received.size + withheldIds.size !== expected.size || (completion.total !== undefined && completion.total !== received.size))
                throw new Error('Incomplete translation stream.');
            return true;
        } finally {
            clearTimeout(timeout);
            this.controllers.delete(controller);
        }
    }

    private async processNdjsonStream(body: ReadableStream<Uint8Array>, onBatch: (translations: unknown[]) => void): Promise<{ complete: boolean; withheldIds: number[]; total?: number }> {
        const reader = body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';
        let complete = false;
        let withheldIds: number[] = [];
        let total: number | undefined;
        const consume = (line: string): void => {
            if (!line.trim()) return;
            const event = JSON.parse(line);
            if (complete) throw new Error('Translation event after terminal completion.');
            if (event.type === 'batch' && Array.isArray(event.translations)) onBatch(event.translations);
            else if (event.type === 'error') throw new Error(`Server error${event.code ? ` [${event.code}]` : ''}: ${event.message || 'Unknown error'}`);
            else if (event.type === 'complete') {
                if (event.success !== true) throw new Error('Translation request did not complete successfully.');
                complete = true;
                if (
                    event.withheld_ids !== undefined &&
                    (!Array.isArray(event.withheld_ids) ||
                        !event.withheld_ids.every((id: unknown) => (typeof id === 'number' || typeof id === 'string') && String(id).trim() !== '' && Number.isInteger(Number(id))))
                )
                    throw new Error('Invalid withheld translation IDs.');
                withheldIds = (event.withheld_ids ?? []).map(Number);
                total = Number.isInteger(event.total) ? event.total : undefined;
            }
        };
        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() ?? '';
                lines.forEach(consume);
            }
            if (buffer.trim()) consume(buffer);
            return { complete, withheldIds, total };
        } finally {
            reader.releaseLock();
        }
    }

    private splitBatches(items: TranslationRequestItemI[]): TranslationRequestItemI[][] {
        const batches: TranslationRequestItemI[][] = [];
        let batch: TranslationRequestItemI[] = [];
        let chars = 0;
        items.forEach((item) => {
            const size = [...item.text].length;
            if (batch.length && (batch.length >= MAX_ITEMS || chars + size > MAX_BATCH_CHARS)) {
                batches.push(batch);
                batch = [];
                chars = 0;
            }
            batch.push(item);
            chars += size;
        });
        if (batch.length) batches.push(batch);
        return batches;
    }

    private identity(item: Pick<TranslationRequestItemI, 'text' | 'type' | 'attr' | 'context'>): string {
        return `${item.type ?? 'text'}\u0000${item.attr ?? ''}\u0000${item.context ?? ''}\u0000${item.text}`;
    }
    private hashValue(value: string): string {
        let hash = 2166136261;
        for (let index = 0; index < value.length; index++) {
            hash ^= value.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }
        return (hash >>> 0).toString(36);
    }
}
