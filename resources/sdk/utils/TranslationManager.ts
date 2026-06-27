import type { TranslationAPIResponseI } from '../types';
import { PageTranslationCache } from './PageTranslationCache';

export class TranslationManager {
    private translationEndpoint: string;
    private apiKey: string | null = null;
    private pageCache: PageTranslationCache;
    private activeController: AbortController | null = null;

    constructor(endpoint?: string) {
        if (!endpoint) {
            throw new Error('A translation API endpoint is required.');
        }

        this.translationEndpoint = endpoint.replace(/\/$/, '');
        this.pageCache = new PageTranslationCache();
    }

    setApiKey(key: string): void {
        this.apiKey = key;
        this.pageCache.setNamespace(this.hashValue(`${this.translationEndpoint}:${key}`));
    }

    setCacheTTL(ttl: number): void {
        this.pageCache.setTTL(ttl);
    }

    clearCache(): void {
        this.pageCache.clearPage();
    }

    getCacheStats() {
        return this.pageCache.getStats();
    }

    getCachedTranslation(text: string, targetLang: string): string | null {
        return this.pageCache.get(text, targetLang);
    }

    setCachedTranslation(text: string, targetLang: string, translation: string): void {
        this.pageCache.set(text, targetLang, translation);
    }

    cancelActiveRequest(): void {
        this.activeController?.abort();
        this.activeController = null;
    }

    async translateBatch(
        items: { id: number; text: string }[],
        sourceLang: string,
        targetLang: string,
        onBatch?: (translations: { id: number; translated: string }[]) => void,
    ): Promise<{ id: number; translated: string }[]> {
        if (!this.apiKey || items.length === 0) {
            return [];
        }

        const validItems = items.filter((item) => typeof item.id !== 'undefined' && typeof item.text === 'string' && item.text.trim());
        if (validItems.length === 0) {
            return [];
        }

        const textToIds = new Map<string, number[]>();
        const textToRepresentative = new Map<string, { id: number; text: string }>();

        validItems.forEach((item) => {
            const normalizedText = item.text.trim();
            if (!textToIds.has(normalizedText)) {
                textToIds.set(normalizedText, []);
                textToRepresentative.set(normalizedText, {
                    id: item.id,
                    text: normalizedText,
                });
            }

            textToIds.get(normalizedText)?.push(item.id);
        });

        const cachedResults: { id: number; translated: string }[] = [];
        const itemsToFetch: { id: number; text: string }[] = [];

        textToIds.forEach((ids, text) => {
            const cached = this.pageCache.get(text, targetLang);
            if (cached) {
                ids.forEach((id) => {
                    cachedResults.push({ id, translated: cached });
                });
                return;
            }

            const representative = textToRepresentative.get(text);
            if (representative) {
                itemsToFetch.push(representative);
            }
        });

        if (cachedResults.length > 0 && onBatch) {
            onBatch(cachedResults);
        }

        if (itemsToFetch.length === 0) {
            return cachedResults;
        }

        const allResults = [...cachedResults];
        const pendingIdToText = new Map<number, string>();

        itemsToFetch.forEach((item) => {
            pendingIdToText.set(item.id, item.text.trim());
        });

        await this.fetchWithStreaming(itemsToFetch, sourceLang, targetLang, (translations) => {
            const expandedTranslations: { id: number; translated: string }[] = [];
            const cacheItems: { text: string; targetLang: string; translated: string }[] = [];

            translations.forEach((translation) => {
                const text = pendingIdToText.get(Number(translation.id));
                if (!text) {
                    return;
                }

                cacheItems.push({
                    text,
                    targetLang,
                    translated: translation.translated,
                });

                textToIds.get(text)?.forEach((id) => {
                    expandedTranslations.push({
                        id,
                        translated: translation.translated,
                    });
                });
            });

            if (cacheItems.length > 0) {
                this.pageCache.setMany(cacheItems);
            }

            if (expandedTranslations.length > 0) {
                allResults.push(...expandedTranslations);
                onBatch?.(expandedTranslations);
            }
        });

        return allResults;
    }

    private async fetchWithStreaming(
        items: { id: number; text: string }[],
        sourceLang: string,
        targetLang: string,
        onBatch: (translations: { id: number; translated: string }[]) => void,
    ): Promise<void> {
        const requestData = {
            source: sourceLang,
            target: targetLang,
            translatables: items.map((item) => ({
                id: item.id,
                text: item.text.trim(),
            })),
        };

        this.cancelActiveRequest();

        const controller = new AbortController();
        this.activeController = controller;
        const timeoutId = setTimeout(() => controller.abort(), 120000);

        try {
            const response = await fetch(`${this.translationEndpoint}/translations`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${this.apiKey}`,
                    'X-Page-Url': encodeURIComponent(window.location.href),
                    'X-Page-Title': encodeURIComponent(document.title),
                },
                body: JSON.stringify(requestData),
                signal: controller.signal,
            });

            if (!response.ok) {
                const errorText = await response.text().catch(() => 'Unknown error');
                throw new Error(`Translation API error [${response.status}]: ${errorText}`);
            }

            if (!response.body) {
                const json = (await response.json()) as TranslationAPIResponseI;
                if (json?.data?.translations && Array.isArray(json.data.translations)) {
                    onBatch(json.data.translations);
                    return;
                }

                throw new Error('Translation response body was empty.');
            }

            await this.processNdjsonStream(response.body, onBatch);
        } catch (error) {
            if (error instanceof Error && error.name === 'AbortError') {
                throw error;
            }

            throw error;
        } finally {
            clearTimeout(timeoutId);

            if (this.activeController === controller) {
                this.activeController = null;
            }
        }
    }

    private async processNdjsonStream(body: ReadableStream<Uint8Array>, onBatch: (translations: { id: number; translated: string }[]) => void): Promise<void> {
        const reader = body.getReader();
        const decoder = new TextDecoder();
        let buffer = '';

        try {
            while (true) {
                const { done, value } = await reader.read();
                if (done) {
                    break;
                }

                buffer += decoder.decode(value, { stream: true });
                const lines = buffer.split('\n');
                buffer = lines.pop() || '';

                for (const line of lines) {
                    const trimmedLine = line.trim();
                    if (!trimmedLine) {
                        continue;
                    }

                    const event = JSON.parse(trimmedLine);
                    if (!event?.type) {
                        continue;
                    }

                    if (event.type === 'batch' && Array.isArray(event.translations)) {
                        const validTranslations = event.translations.filter((translation: unknown) => {
                            if (!translation || typeof translation !== 'object') {
                                return false;
                            }

                            const item = translation as { id?: unknown; translated?: unknown };
                            return typeof item.id !== 'undefined' && typeof item.translated === 'string';
                        });

                        if (validTranslations.length > 0) {
                            onBatch(validTranslations);
                        }
                        continue;
                    }

                    if (event.type === 'error') {
                        throw new Error(`Server error: ${event.message || 'Unknown error'}`);
                    }
                }
            }

            if (buffer.trim()) {
                const event = JSON.parse(buffer.trim());
                if (event?.type === 'batch' && Array.isArray(event.translations)) {
                    onBatch(event.translations);
                }
            }
        } finally {
            reader.releaseLock();
        }
    }

    private hashValue(value: string): string {
        let hash = 2166136261;

        for (let index = 0; index < value.length; index += 1) {
            hash ^= value.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }

        return (hash >>> 0).toString(36);
    }
}
