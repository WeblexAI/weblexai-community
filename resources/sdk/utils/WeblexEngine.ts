import { createApp, h, reactive } from 'vue';
import type { LanguageI, ProjectConfigI, TranslationDebugSnapshotI, TranslationRequestItemI, TranslationResponseItemI, WeblexAIEnginePublicI } from '../types';
import SwitcherIndex from '../ui/SwitcherIndex.vue';
import { resolveApiEndpoint } from './ApiEndpoint';
import { DomObserver } from './DomObserver';
import { LocalStore } from './LocalStore';
import { NodeRegistry } from './NodeRegistry';
import { TranslationManager } from './TranslationManager';

type Target = { node: Text | Element; type: 'text' | 'attr'; attr: string };
const ATTRIBUTES = ['placeholder', 'alt', 'title', 'aria-label', 'aria-description'] as const;

export default class WeblexAIEngine implements WeblexAIEnginePublicI {
    public projectConfig: ProjectConfigI | null = null;
    public state = reactive({ languages: [] as LanguageI[], selectedLang: null as LanguageI | null, loading: false });
    private apiKey: string | null = null;
    private currentLanguageIso2: string | null = null;
    private originalLanguageIso2: string | null = null;
    private targets = new Map<string, Target>();
    private targetsById = new Map<number, Target>();
    private registry = new NodeRegistry();
    private store = new LocalStore();
    private manager: TranslationManager;
    private observer: DomObserver;
    private activeRun = 0;
    private observerPaused = 0;
    private mutationTimer: number | null = null;
    private pendingRoots = new Set<Node>();

    constructor(private readonly endpoint = resolveApiEndpoint()) {
        this.manager = new TranslationManager(endpoint);
        this.observer = new DomObserver(
            (nodes) => this.handleMutations(nodes),
            () => this.cleanup(),
        );
    }

    async init(apiKey: string): Promise<void> {
        this.apiKey = apiKey;
        this.manager.setApiKey(apiKey);
        const config = await this.getProjectConfig();
        this.projectConfig = config.is_active ? config : null;
        if (!this.projectConfig) return;
        this.manager.setCacheRevision(this.projectConfig.delivery_revision);
        this.originalLanguageIso2 = this.projectConfig.original_language.iso_2;
        this.currentLanguageIso2 = (await this.store.get('CURRENT_LANGUAGE_ISO2', this.originalLanguageIso2)) ?? this.originalLanguageIso2;
        this.discover(document.body);
        this.observer.start();
        this.state.languages = this.projectConfig.languages;
        this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === this.currentLanguageIso2) ?? this.state.languages[0] ?? null;
        if (this.currentLanguageIso2) await this.translateTo(this.currentLanguageIso2, true);
        if (this.state.languages.length) this.mountVueUI();
    }

    async translateTo(targetLangIso2: string, isInitialLoad = false): Promise<void> {
        if (!this.apiKey || !targetLangIso2) return;
        const run = ++this.activeRun;
        this.manager.cancelActiveRequest();
        if (targetLangIso2 === this.originalLanguageIso2) {
            this.restoreOriginals();
            this.currentLanguageIso2 = targetLangIso2;
            this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === targetLangIso2) ?? null;
            this.state.loading = false;
            await this.store.set('CURRENT_LANGUAGE_ISO2', targetLangIso2);
        }
        const fresh = await this.getProjectConfig();
        if (run !== this.activeRun) return;
        if (!fresh.is_active) {
            if (!('unavailable' in fresh)) {
                this.restoreOriginals();
                this.projectConfig = null;
            } else if (targetLangIso2 === this.originalLanguageIso2) {
                this.restoreOriginals();
                this.currentLanguageIso2 = targetLangIso2;
                this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === targetLangIso2) ?? null;
                await this.store.set('CURRENT_LANGUAGE_ISO2', targetLangIso2);
            }
            if (run === this.activeRun) this.state.loading = false;
            return;
        }
        if (run !== this.activeRun) return;
        const revisionChanged = fresh.delivery_revision !== this.projectConfig?.delivery_revision;
        if (revisionChanged) {
            this.restoreOriginals();
            this.clearKnownTranslations();
            this.manager.setCacheRevision(fresh.delivery_revision);
            this.projectConfig = fresh;
            this.originalLanguageIso2 = fresh.original_language.iso_2;
            this.state.languages = fresh.languages;
            this.discover(document.body);
        }
        if (!fresh.languages.some((language) => language.iso_2 === targetLangIso2)) {
            this.restoreOriginals();
            this.currentLanguageIso2 = this.originalLanguageIso2;
            return;
        }
        if (!isInitialLoad && !revisionChanged && targetLangIso2 !== this.originalLanguageIso2 && targetLangIso2 === this.currentLanguageIso2 && !this.state.loading) return;
        this.state.loading = true;
        try {
            if (targetLangIso2 === this.originalLanguageIso2) this.restoreOriginals();
            else await this.translate(targetLangIso2, run);
            if (run !== this.activeRun) return;
            this.currentLanguageIso2 = targetLangIso2;
            this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === targetLangIso2) ?? null;
            await this.store.set('CURRENT_LANGUAGE_ISO2', targetLangIso2);
        } catch (error) {
            if (!(error instanceof Error && error.name === 'AbortError')) console.error('Translation run failed:', error);
        } finally {
            if (run === this.activeRun) this.state.loading = false;
        }
    }

    clearCache(): void {
        this.manager.clearCache();
    }
    getDebugSnapshot(): TranslationDebugSnapshotI {
        return {
            currentLanguageIso2: this.currentLanguageIso2,
            originalLanguageIso2: this.originalLanguageIso2,
            trackedNodeCount: this.targets.size,
            pendingIncrementalNodeCount: this.pendingRoots.size,
            cache: this.manager.getCacheStats(),
        };
    }

    private async translate(targetLang: string, run: number): Promise<void> {
        if (!this.originalLanguageIso2 || !this.projectConfig) return;
        this.cleanup();
        this.targetsById.clear();
        const items: TranslationRequestItemI[] = [];
        this.restoreOriginals();
        this.targets.forEach((target) => {
            const metadata = this.registry.getTarget(target.node, target.type, target.attr);
            if (!metadata || !target.node.isConnected || this.isExcluded(target.node, metadata.type)) return;
            this.targetsById.set(metadata.translation_id, target);
            const known = metadata.language_translations.find((translation) => translation.language === targetLang)?.translated;
            if (known) {
                this.apply([{ id: metadata.translation_id, translated: known }], targetLang);
                return;
            }
            items.push({ id: metadata.translation_id, text: metadata.translation_original, type: metadata.type, attr: metadata.attr, context: metadata.context });
        });
        await this.manager.translateBatch(items, this.originalLanguageIso2, targetLang, (rows) => {
            if (run === this.activeRun) this.apply(rows, targetLang);
        });
    }

    private apply(rows: Array<Pick<TranslationResponseItemI, 'id' | 'translated'>>, language: string): void {
        this.withObserverPaused(() =>
            rows.forEach((row) => {
                const target = this.targetsById.get(Number(row.id));
                const metadata = target && this.registry.getTarget(target.node, target.type, target.attr);
                if (!target || !metadata || !target.node.isConnected || this.isExcluded(target.node, metadata.type)) return;
                const source = metadata.translation_original;
                const translated = metadata.type === 'text' ? `${source.match(/^\s*/)?.[0] ?? ''}${row.translated.trim()}${source.match(/\s*$/)?.[0] ?? ''}` : row.translated;
                this.registry.updateTargetTranslation(target.node, target.type, target.attr, language, translated);
                if (metadata.type === 'text' && target.node.nodeType === Node.TEXT_NODE) target.node.textContent = translated;
                else if (target.node instanceof Element && ATTRIBUTES.includes(metadata.attr as (typeof ATTRIBUTES)[number])) target.node.setAttribute(metadata.attr, translated);
            }),
        );
    }

    private restoreOriginals(): void {
        this.withObserverPaused(() =>
            this.targets.forEach((target) => {
                const metadata = this.registry.getTarget(target.node, target.type, target.attr);
                if (!metadata || this.isExcluded(target.node, target.type)) return;
                if (metadata.type === 'text' && target.node.nodeType === Node.TEXT_NODE) target.node.textContent = metadata.translation_original;
                else if (target.node instanceof Element && metadata.attr) target.node.setAttribute(metadata.attr, metadata.translation_original);
            }),
        );
    }
    private clearKnownTranslations(): void {
        this.targets.forEach((target) => {
            const metadata = this.registry.getTarget(target.node, target.type, target.attr);
            if (!metadata) return;
            metadata.language_translations.forEach((translation) => {
                translation.translated = translation.language === this.originalLanguageIso2 ? metadata.translation_original : null;
            });
        });
    }

    private discover(root: Node | Node[]): void {
        const roots = Array.isArray(root) ? root : [root];
        roots.forEach((candidate) => {
            if (candidate.nodeType === Node.TEXT_NODE) this.registerText(candidate as Text);
            const walker = document.createTreeWalker(candidate, NodeFilter.SHOW_TEXT);
            let current: Node | null;
            while ((current = walker.nextNode())) this.registerText(current as Text);
            const element = candidate.nodeType === Node.ELEMENT_NODE ? (candidate as Element) : candidate.parentElement;
            if (!element) return;
            [element, ...Array.from(element.querySelectorAll('*'))].forEach((node) => ATTRIBUTES.forEach((attr) => this.registerAttribute(node, attr)));
        });
        this.targets.forEach((target) => {
            if (target.type !== 'text' || !(target.node instanceof Text)) return;
            const metadata = this.registry.getTarget(target.node, 'text');
            if (metadata)
                this.registry.registerTarget(
                    target.node,
                    metadata.translation_original,
                    this.projectConfig?.languages ?? [],
                    this.originalLanguageIso2 ?? '',
                    'text',
                    '',
                    this.contextFor(target.node),
                );
        });
    }

    private registerText(node: Text): void {
        const text = node.textContent ?? '';
        if (this.isExcluded(node)) {
            this.unregisterTarget(node, 'text', '');
            return;
        }
        if (!this.valid(text)) {
            this.targets.delete(this.targetKey(node, 'text', ''));
            const previous = this.registry.getTarget(node, 'text');
            if (previous) {
                this.targetsById.delete(previous.translation_id);
                this.registry.registerTarget(node, text, this.projectConfig?.languages ?? [], this.originalLanguageIso2 ?? '', 'text');
            }
            return;
        }
        this.registry.registerTarget(node, text, this.projectConfig?.languages ?? [], this.originalLanguageIso2 ?? '', 'text', '', this.contextFor(node));
        this.targets.set(this.targetKey(node, 'text', ''), { node, type: 'text', attr: '' });
    }
    private registerAttribute(element: Element, attr: (typeof ATTRIBUTES)[number]): void {
        const value = element.getAttribute(attr);
        if (this.isExcluded(element, 'attr')) {
            this.unregisterTarget(element, 'attr', attr);
            return;
        }
        if (!value || !this.valid(value)) {
            this.targets.delete(this.targetKey(element, 'attr', attr));
            const previous = this.registry.getTarget(element, 'attr', attr);
            if (previous) {
                this.targetsById.delete(previous.translation_id);
                if (value !== null) this.registry.registerTarget(element, value, this.projectConfig?.languages ?? [], this.originalLanguageIso2 ?? '', 'attr', attr);
            }
            return;
        }
        this.registry.registerTarget(element, value, this.projectConfig?.languages ?? [], this.originalLanguageIso2 ?? '', 'attr', attr, '');
        this.targets.set(this.targetKey(element, 'attr', attr), { node: element, type: 'attr', attr });
    }

    private contextFor(node: Text): string {
        const parent = node.parentElement;
        if (!parent) return '';
        const block =
            parent.closest('address, article, blockquote, button, dd, div, dt, figcaption, footer, h1, h2, h3, h4, h5, h6, header, label, li, main, nav, p, section, summary, td, th') ?? parent;
        const walker = document.createTreeWalker(block, NodeFilter.SHOW_TEXT);
        const parts: string[] = [];
        let current: Node | null;
        while ((current = walker.nextNode())) {
            const nestedBlock = (current as Text).parentElement?.closest(
                'address, article, blockquote, button, dd, div, dt, figcaption, footer, h1, h2, h3, h4, h5, h6, header, label, li, main, nav, p, section, summary, td, th',
            );
            const metadata = this.registry.getTarget(current, 'text', '');
            const text = metadata?.translation_original.trim() ?? current.textContent?.trim() ?? '';
            if (text && nestedBlock === block && !this.isExcluded(current as Text)) parts.push(text);
        }
        return parts.join(' ').slice(0, 10_000);
    }
    private isExcluded(target: Text | Element, type: 'text' | 'attr' = 'text'): boolean {
        const element: Element | null = target instanceof Element ? target : target.parentElement;
        if (!element) return true;
        if (element.closest('script, style, noscript, template, code, pre, [translate="no"], select, option')) return true;
        if (element.closest('[contenteditable]:not([contenteditable="false"])')) return true;
        if (type === 'text' && element.closest('textarea, input')) return true;
        if (element.closest('input[type="password"], [data-weblex-exclude]')) return true;
        return (this.projectConfig?.excluded_blocks ?? []).some((selector) => {
            try {
                return Boolean(element.closest(selector));
            } catch {
                return false;
            }
        });
    }
    private valid(text: string): boolean {
        const value = text.trim();
        return Boolean(value && /\p{L}/u.test(value) && !/^(https?:\/\/|www\.|\S+@\S+\.\S+)/i.test(value) && !/^[\w-]+(?:=|:)/.test(value));
    }
    private cleanup(): void {
        this.targets.forEach((target, key) => {
            if (!target.node.isConnected) this.targets.delete(key);
            else if (target.type === 'attr' && target.node instanceof Element && !target.node.hasAttribute(target.attr)) this.targets.delete(key);
        });
    }
    private unregisterTarget(node: Text | Element, type: 'text' | 'attr', attr: string): void {
        this.targets.delete(this.targetKey(node, type, attr));
        const metadata = this.registry.getTarget(node, type, attr);
        if (metadata) this.targetsById.delete(metadata.translation_id);
    }
    private handleMutations(nodes: Node[]): void {
        if (this.observerPaused) return;
        nodes.forEach((node) => {
            const element = node instanceof Element ? node : node.parentElement;
            if (element?.closest('[data-weblex-exclude]')) return;
            this.pendingRoots.add(node);
        });
        if (!this.pendingRoots.size) return;
        if (this.mutationTimer) clearTimeout(this.mutationTimer);
        this.mutationTimer = window.setTimeout(() => {
            this.mutationTimer = null;
            const roots = [...this.pendingRoots];
            this.pendingRoots.clear();
            this.discover(roots);
            if (this.currentLanguageIso2 && this.currentLanguageIso2 !== this.originalLanguageIso2) {
                const run = ++this.activeRun;
                this.manager.cancelActiveRequest();
                void this.translate(this.currentLanguageIso2, run).catch((error: unknown) => {
                    if (!(error instanceof Error && error.name === 'AbortError')) console.error('Translation run failed:', error);
                });
            }
        }, 150);
    }
    private withObserverPaused(callback: () => void): void {
        const restart = this.observerPaused++ === 0;
        if (restart) this.observer.stop();
        try {
            callback();
        } finally {
            this.observerPaused--;
            if (restart) this.observer.start();
        }
    }
    private targetKey(node: Text | Element, type: 'text' | 'attr', attr: string): string {
        return `${type}:${attr}:${this.registry.getTarget(node, type, attr)?.translation_id ?? ''}`;
    }
    private async getProjectConfig(): Promise<ProjectConfigI | { is_active: false; unavailable?: true }> {
        if (!this.apiKey) return { is_active: false, unavailable: true };
        try {
            const response = await fetch(`${this.endpoint.replace(/\/$/, '')}/config`, {
                headers: { Authorization: `Bearer ${this.apiKey}`, 'X-Page-Url': encodeURIComponent(window.location.href), 'X-Page-Title': encodeURIComponent(document.title) },
            });
            if (!response.ok) return { is_active: false, unavailable: true };
            return ((await response.json()) as { data?: ProjectConfigI }).data ?? { is_active: false, unavailable: true };
        } catch {
            return { is_active: false, unavailable: true };
        }
    }
    private mountVueUI(): void {
        let container: Element | null = null;
        const selector = this.projectConfig?.switcher_config.target_parent_selector;
        if (selector) {
            try {
                container = document.querySelector(selector);
            } catch {
                container = null;
            }
        }
        const isEmbedded = Boolean(container);
        if (!container) {
            container = document.getElementById('weblexai-root');
        }
        if (!container) {
            container = document.createElement('div');
            container.id = 'weblexai-root';
            document.body.appendChild(container);
        }
        container.setAttribute('data-weblex-exclude', '');
        createApp({ render: () => h(SwitcherIndex, { state: this.state, engine: this, isEmbedded }) }).mount(container);
    }
}
