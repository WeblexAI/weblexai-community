import { describe, expect, it, vi } from 'vitest';
import { TranslationManager } from '../../resources/sdk/utils/TranslationManager';

describe('TranslationManager', () => {
    it('bounds Unicode source characters and keeps at most two requests in flight', async () => {
        let active = 0;
        let maximum = 0;
        const sizes: number[] = [];
        vi.stubGlobal(
            'fetch',
            vi.fn(async (_url: string, request: RequestInit) => {
                const body = JSON.parse(String(request.body)) as { translatables: Array<{ id: number; text: string }> };
                sizes.push(body.translatables.reduce((total, item) => total + [...item.text].length, 0));
                maximum = Math.max(maximum, ++active);
                await new Promise((resolve) => setTimeout(resolve, 10));
                active--;
                return { ok: true, body: null, json: async () => ({ data: { translations: body.translatables.map((item) => ({ ...item, translated: 'Translated' })) } }) };
            }),
        );
        const manager = new TranslationManager('https://example.test/api');
        manager.setApiKey('character-test');
        const rows = await manager.translateBatch(
            Array.from({ length: 5 }, (_, index) => ({ id: index + 1, text: `${index}${'界'.repeat(9_999)}`, type: 'text' as const })),
            'en',
            'fr',
        );
        expect(sizes).toEqual([20_000, 20_000, 10_000]);
        expect(maximum).toBe(2);
        expect(rows).toHaveLength(5);
    });
    it('splits more than one hundred items into bounded requests', async () => {
        const fetchMock = vi.fn(async (_url: string, request: RequestInit) => {
            const body = JSON.parse(String(request.body)) as { translatables: Array<{ id: number; text: string }> };
            return { ok: true, body: null, json: async () => ({ data: { translations: body.translatables.map((item) => ({ ...item, translated: `fr:${item.text}` })) } }) };
        });
        vi.stubGlobal('fetch', fetchMock);
        const manager = new TranslationManager('https://example.test/api');
        manager.setApiKey('key');
        const results = await manager.translateBatch(
            Array.from({ length: 101 }, (_, index) => ({ id: index + 1, text: `Item number ${index + 1}`, type: 'text' as const })),
            'en',
            'fr',
        );
        expect(fetchMock).toHaveBeenCalledTimes(2);
        expect(results).toHaveLength(101);
    });

    it('does not merge text and attribute identities in the cache', async () => {
        const fetchMock = vi.fn(async (_url: string, request: RequestInit) => {
            const body = JSON.parse(String(request.body)) as { translatables: Array<{ id: number; text: string; type: string; attr?: string }> };
            return { ok: true, body: null, json: async () => ({ data: { translations: body.translatables.map((item) => ({ ...item, translated: `${item.type}:${item.text}` })) } }) };
        });
        vi.stubGlobal('fetch', fetchMock);
        const manager = new TranslationManager('https://example.test/api');
        manager.setApiKey('key');
        const results = await manager.translateBatch(
            [
                { id: 1, text: 'Search', type: 'text' },
                { id: 2, text: 'Search', type: 'attr', attr: 'placeholder' },
            ],
            'en',
            'fr',
        );
        expect(results.map((result) => result.translated)).toEqual(['text:Search', 'attr:Search']);
        expect(fetchMock).toHaveBeenCalledTimes(1);
    });

    it('rejects a stream that ends without a completion event', async () => {
        const stream = new ReadableStream<Uint8Array>({
            start(controller) {
                controller.enqueue(new TextEncoder().encode('{"type":"batch","translations":[{"id":1,"translated":"Bonjour"}]}\n'));
                controller.close();
            },
        });
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({ ok: true, body: stream })),
        );
        const manager = new TranslationManager('https://example.test/api');
        manager.setApiKey('key');
        await expect(manager.translateBatch([{ id: 1, text: 'Hello', type: 'text' }], 'en', 'fr')).rejects.toThrow('Incomplete translation stream');
    });

    it('accepts an explicitly withheld item without caching or returning it', async () => {
        const stream = new ReadableStream<Uint8Array>({
            start(controller) {
                controller.enqueue(new TextEncoder().encode('{"type":"batch","translations":[{"id":1,"translated":"Bonjour"}]}\n{"type":"complete","total":1,"withheld_ids":["2"],"success":true}\n'));
                controller.close();
            },
        });
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => ({ ok: true, body: stream })),
        );
        const manager = new TranslationManager('https://example.test/api');
        manager.setApiKey('key');
        const results = await manager.translateBatch(
            [
                { id: 1, text: 'Hello', type: 'text' },
                { id: 2, text: 'Private', type: 'text' },
            ],
            'en',
            'fr',
        );
        expect(results).toEqual([expect.objectContaining({ id: 1, translated: 'Bonjour' })]);
    });
});
