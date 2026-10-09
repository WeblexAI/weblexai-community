import { describe, expect, it, vi } from 'vitest';
import WeblexAIEngine from '../../resources/sdk/utils/WeblexEngine';

const config = {
    is_active: true,
    delivery_revision: 1,
    original_language: { id: 1, name: 'English', iso_2: 'en', flag: '' },
    languages: [
        { id: 1, name: 'English', iso_2: 'en', flag: '' },
        { id: 2, name: 'French', iso_2: 'fr', flag: '' },
    ],
    excluded_blocks: ['.exclude'],
    page: '/',
    hide_water_mark: false,
    switcher_config: {
        target_parent_selector: '#switcher',
        should_display_name: true,
        should_display_full_name: true,
        should_display_flag: false,
        size: 50,
        should_open_on_hover: false,
        should_close_on_outside_click: true,
        should_show_by_device: false,
        preferred_device: null,
        device_pixel_breakpoint: 768,
    },
};

function apiMock(currentConfig = config) {
    return vi.fn(async (url: string, request?: RequestInit) => {
        if (url.endsWith('/config')) return { ok: true, json: async () => ({ data: structuredClone(currentConfig) }) };
        const body = JSON.parse(String(request?.body)) as { translatables: Array<{ id: number; text: string; type?: string; attr?: string; context?: string }> };
        return { ok: true, body: null, json: async () => ({ data: { translations: body.translatables.map((item) => ({ ...item, translated: `FR:${item.text.trim()}` })) } }) };
    });
}

describe('WeblexAIEngine coverage', () => {
    it('ignores switcher mutations and translates new content without refreshing configuration', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="mount"></div><div id="switcher"></div></main>';
        const fetchMock = apiMock();
        vi.stubGlobal('fetch', fetchMock);
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        const configRequests = fetchMock.mock.calls.filter(([url]) => String(url).endsWith('/config')).length;
        const label = document.createElement('span');
        label.textContent = 'French';
        document.getElementById('switcher')!.appendChild(label);
        const paragraph = document.createElement('p');
        paragraph.textContent = 'New content';
        document.getElementById('mount')!.appendChild(paragraph);
        await vi.waitFor(() => expect(paragraph.textContent).toBe('FR:New content'));
        await new Promise((resolve) => setTimeout(resolve, 400));
        expect(label.textContent).toBe('French');
        expect(fetchMock.mock.calls.filter(([url]) => String(url).endsWith('/config'))).toHaveLength(configRequests);
        await engine.translateTo('en');
        expect(paragraph.textContent).toBe('New content');
    });

    it('restores English before a delayed configuration request finishes', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="switcher"></div></main>';
        const fetchMock = apiMock();
        vi.stubGlobal('fetch', fetchMock);
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        let finish: ((value: any) => void) | undefined;
        fetchMock.mockImplementationOnce(
            () =>
                new Promise((resolve) => {
                    finish = resolve;
                }),
        );
        const english = engine.translateTo('en');
        expect(document.querySelector('p')!.textContent).toBe('Hello world');
        expect(engine.state.selectedLang?.iso_2).toBe('en');
        await vi.waitFor(() => expect(finish).toBeDefined());
        finish!({ ok: false, status: 429 });
        await english;
        expect(engine.getDebugSnapshot().currentLanguageIso2).toBe('en');
    });

    it('translates allowed attributes, preserves text node identity, and restores originals', async () => {
        document.body.innerHTML =
            '<main><button id="button" title="Open settings"> Hello <strong>world</strong> today </button><input id="search" placeholder="Search docs"><input id="secret" type="password" placeholder="Never translate"><div id="switcher"></div></main>';
        const buttonText = document.getElementById('button')?.firstChild;
        const onClick = vi.fn();
        document.getElementById('button')?.addEventListener('click', onClick);
        vi.stubGlobal('fetch', apiMock());
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        const button = document.getElementById('button') as HTMLButtonElement;
        expect(button.firstChild).toBe(buttonText);
        button.click();
        expect(onClick).toHaveBeenCalledOnce();
        expect(button.title).toBe('FR:Open settings');
        expect((document.getElementById('search') as HTMLInputElement).placeholder).toBe('FR:Search docs');
        expect((document.getElementById('secret') as HTMLInputElement).placeholder).toBe('Never translate');
        await engine.translateTo('en');
        expect(button.title).toBe('Open settings');
        expect((document.getElementById('search') as HTMLInputElement).placeholder).toBe('Search docs');
        expect(button.textContent).toContain('Hello');
    });

    it('uses the nearest block context across inline text and omits excluded content', async () => {
        document.body.innerHTML = '<main><p id="copy">Hello <strong>world</strong> today <span class="exclude">private</span></p><p>Unrelated sentence</p></main>';
        const engine = new WeblexAIEngine('https://example.test/api/project');
        (engine as any).projectConfig = config;
        const copy = document.getElementById('copy') as HTMLParagraphElement;
        const first = copy.firstChild as Text;
        expect((engine as any).contextFor(first)).toBe('Hello world today');
    });

    it('does not apply late results after a language change', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="switcher"></div></main>';
        let resolveFrench: ((value: unknown) => void) | undefined;
        const fetchMock = vi.fn((url: string, request?: RequestInit) => {
            if (url.endsWith('/config')) return Promise.resolve({ ok: true, json: async () => ({ data: config }) });
            const body = JSON.parse(String(request?.body)) as { target: string; translatables: Array<{ id: number; text: string }> };
            if (body.target === 'fr')
                return new Promise((resolve) => {
                    resolveFrench = resolve;
                });
            return Promise.resolve({ ok: true, body: null, json: async () => ({ data: { translations: body.translatables.map((item) => ({ ...item, translated: item.text })) } }) });
        });
        vi.stubGlobal('fetch', fetchMock);
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        const french = engine.translateTo('fr');
        await engine.translateTo('en');
        resolveFrench?.({ ok: true, body: null, json: async () => ({ data: { translations: [] } }) });
        await french;
        expect(document.querySelector('p')?.textContent).toBe('Hello world');
    });

    it('restores partially applied batches when returning to the source language', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="switcher"></div></main>';
        vi.stubGlobal('fetch', apiMock());
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        let finish: (() => void) | undefined;
        let deliver: ((rows: Array<{ id: number; translated: string }>) => void) | undefined;
        let id = 0;
        vi.spyOn((engine as any).manager, 'translateBatch').mockImplementation((...args: unknown[]) => {
            id = (args[0] as Array<{ id: number }>)[0].id;
            deliver = args[3] as typeof deliver;
            deliver?.([{ id, translated: 'Bonjour' }]);
            return new Promise<void>((resolve) => {
                finish = resolve;
            });
        });
        const french = engine.translateTo('fr');
        await vi.waitFor(() => expect(document.querySelector('p')?.textContent).toBe('Bonjour'));
        await engine.translateTo('en');
        expect(document.querySelector('p')?.textContent).toBe('Hello world');
        deliver?.([{ id, translated: 'Late result' }]);
        finish?.();
        await french;
        expect(document.querySelector('p')?.textContent).toBe('Hello world');
    });

    it('translates dynamically added allowlisted attributes', async () => {
        document.body.innerHTML = '<main><div id="mount"></div><div id="switcher"></div></main>';
        vi.stubGlobal('fetch', apiMock());
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        const input = document.createElement('input');
        input.setAttribute('placeholder', 'Find products');
        document.getElementById('mount')?.appendChild(input);
        await new Promise((resolve) => setTimeout(resolve, 220));
        expect(input.placeholder).toBe('FR:Find products');
    });

    it('invalidates in-memory translations and refetches when delivery revision changes', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="switcher"></div></main>';
        const currentConfig = { ...config };
        const fetchMock = apiMock(currentConfig);
        vi.stubGlobal('fetch', fetchMock);
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        currentConfig.delivery_revision = 2;
        await engine.translateTo('fr');
        expect(fetchMock.mock.calls.filter(([url]) => String(url).endsWith('/translations'))).toHaveLength(2);
        expect(document.querySelector('p')?.textContent).toBe('FR:Hello world');
    });

    it('keeps externally cleared text and attributes empty on source restoration', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><input placeholder="Search docs"><div id="switcher"></div></main>';
        vi.stubGlobal('fetch', apiMock());
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        const text = document.querySelector('p')!.firstChild!;
        text.textContent = '';
        document.querySelector('input')!.setAttribute('placeholder', '');
        await new Promise((resolve) => setTimeout(resolve, 220));
        await engine.translateTo('en');
        expect(text.textContent).toBe('');
        expect(document.querySelector('input')!.placeholder).toBe('');
    });

    it('does not restore content that becomes excluded after translation', async () => {
        document.body.innerHTML = '<main><p title="Read more">Hello world</p><div id="switcher"></div></main>';
        vi.stubGlobal('fetch', apiMock());
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        const paragraph = document.querySelector('p')!;
        paragraph.setAttribute('translate', 'no');
        paragraph.firstChild!.textContent = 'Host-owned text';
        paragraph.title = 'Host-owned title';
        await engine.translateTo('en');
        expect(paragraph.textContent).toBe('Host-owned text');
        expect(paragraph.title).toBe('Host-owned title');
    });

    it('restores source content when configuration refresh is unavailable', async () => {
        document.body.innerHTML = '<main><p>Hello world</p><div id="switcher"></div></main>';
        const fetchMock = apiMock();
        vi.stubGlobal('fetch', fetchMock);
        const engine = new WeblexAIEngine('https://example.test/api/project');
        await engine.init('key');
        await engine.translateTo('fr');
        fetchMock.mockResolvedValueOnce({ ok: false } as any);
        await engine.translateTo('en');
        expect(document.querySelector('p')?.textContent).toBe('Hello world');
        expect(engine.getDebugSnapshot().currentLanguageIso2).toBe('en');
    });
});
