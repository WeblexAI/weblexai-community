import { createApp, h, reactive } from 'vue';
import type { LanguageI, ProjectConfigI, TranslationDebugSnapshotI, WeblexAIEnginePublicI } from '../types';
import SwitcherIndex from '../ui/SwitcherIndex.vue';
import { resolveApiEndpoint } from './ApiEndpoint';
import { DomObserver } from './DomObserver';
import { LocalStore } from './LocalStore';
import { NodeRegistry } from './NodeRegistry';
import { TranslationManager } from './TranslationManager';

type PendingFullTranslation = {
    isInitialLoad: boolean;
    targetLangIso2: string;
};

export default class WeblexAIEngine implements WeblexAIEnginePublicI {
    private apiKey: string | null = null;
    public projectConfig: ProjectConfigI | null = null;
    private currentLanguageIso2: string | null = null;
    private originalLanguageIso2: string | null = null;

    private domNodes = new Set<Text>();
    private nodeToIdMap = new Map<Text, number>();
    private idToNodeMap = new Map<number, Text>();
    private store = new LocalStore();
    private mutationDebounceTimer: number | null = null;
    private isTranslating = false;
    private pendingFullTranslation: PendingFullTranslation | null = null;
    private pendingIncrementalNodes = new Set<Text>();
    private debouncePendingNodes = new Set<Node>();
    private activeRunId = 0;
    private observerPauseDepth = 0;

    private nodeRegistry = new NodeRegistry();
    private translationManager: TranslationManager;
    private domObserver: DomObserver;
    private endpoint: string;

    constructor(endpoint?: string) {
        this.endpoint = endpoint?.replace(/\/$/, '') ?? resolveApiEndpoint();

        this.translationManager = new TranslationManager(this.endpoint);
        this.domObserver = new DomObserver(this.handleMutations.bind(this), this.handleRemovedNodes.bind(this));
    }

    public state = reactive({
        languages: [] as LanguageI[],
        selectedLang: null as LanguageI | null,
        loading: false,
    });

    async init(apiKey: string): Promise<void> {
        this.apiKey = apiKey;
        this.translationManager.setApiKey(apiKey);

        const config = await this.getProjectConfig();
        this.projectConfig = config.is_active ? config : null;

        if (!this.projectConfig?.is_active) {
            return;
        }

        this.originalLanguageIso2 = this.projectConfig.original_language.iso_2;
        const storedLanguage = await this.store.get<string>('CURRENT_LANGUAGE_ISO2', this.originalLanguageIso2);
        this.currentLanguageIso2 = storedLanguage ?? this.originalLanguageIso2;

        if (this.domNodes.size === 0) {
            this.extractFlatTextNodes(document.body).forEach((node) => this.domNodes.add(node));
            await this.translateTo(this.currentLanguageIso2, true);
            this.pendingIncrementalNodes.clear();
            this.debouncePendingNodes.clear();
        }

        this.domObserver.start();

        this.state.languages = this.projectConfig.languages;
        this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === this.currentLanguageIso2) ?? this.state.languages[0] ?? null;

        if (this.state.languages.length > 0) {
            this.mountVueUI();
        }
    }

    async translateTo(targetLangIso2: string, isInitialLoad = false): Promise<void> {
        if (!targetLangIso2 || !this.apiKey) {
            return;
        }

        if (!isInitialLoad && targetLangIso2 === this.currentLanguageIso2 && this.pendingIncrementalNodes.size === 0) {
            return;
        }

        this.pendingFullTranslation = {
            isInitialLoad,
            targetLangIso2,
        };

        if (this.isTranslating) {
            this.activeRunId += 1;
            this.translationManager.cancelActiveRequest();
            return;
        }

        await this.processQueuedTranslations();
    }

    clearCache(): void {
        this.translationManager.clearCache();
    }

    getDebugSnapshot(): TranslationDebugSnapshotI {
        return {
            currentLanguageIso2: this.currentLanguageIso2,
            originalLanguageIso2: this.originalLanguageIso2,
            trackedNodeCount: this.domNodes.size,
            pendingIncrementalNodeCount: this.pendingIncrementalNodes.size,
            cache: this.translationManager.getCacheStats(),
        };
    }

    private mountVueUI(): void {
        const targetSelector = this.projectConfig?.switcher_config.target_parent_selector;
        let container = targetSelector ? (document.querySelector(targetSelector) as HTMLElement | null) : null;

        if (!container) {
            container = document.getElementById('weblexai-root');
        }

        if (!container) {
            container = document.createElement('div');
            container.id = 'weblexai-root';
            container.style.position = 'fixed';
            container.style.bottom = '20px';
            container.style.right = '20px';
            container.style.zIndex = '99999';
            document.body.appendChild(container);
        }

        const app = createApp({
            render: () =>
                h(SwitcherIndex, {
                    state: this.state,
                    engine: this,
                }),
        });

        app.mount(container);
    }

    private async getProjectConfig(): Promise<ProjectConfigI | { is_active: false }> {
        if (!this.apiKey) {
            return { is_active: false };
        }

        try {
            const response = await fetch(`${this.endpoint}/config`, {
                method: 'GET',
                headers: {
                    Authorization: `Bearer ${this.apiKey}`,
                    'Content-Type': 'application/json',
                    'X-Page-Url': encodeURIComponent(window.location.href),
                    'X-Page-Title': encodeURIComponent(document.title),
                },
            });

            if (!response.ok) {
                return { is_active: false };
            }

            const payload = (await response.json()) as { data?: ProjectConfigI };
            return payload.data ?? { is_active: false };
        } catch {
            return { is_active: false };
        }
    }

    private async processQueuedTranslations(): Promise<void> {
        if (this.isTranslating) {
            return;
        }

        while (this.pendingFullTranslation || this.pendingIncrementalNodes.size > 0) {
            if (this.pendingFullTranslation) {
                const pending = this.pendingFullTranslation;
                this.pendingFullTranslation = null;
                this.pendingIncrementalNodes.clear();

                await this.performTranslationRun(pending.targetLangIso2, Array.from(this.domNodes), false);
                continue;
            }

            if (!this.currentLanguageIso2 || this.currentLanguageIso2 === this.originalLanguageIso2) {
                break;
            }

            const incrementalNodes = this.getConnectedUniqueNodes(Array.from(this.pendingIncrementalNodes));
            this.pendingIncrementalNodes.clear();

            if (incrementalNodes.length === 0) {
                continue;
            }

            await this.performTranslationRun(this.currentLanguageIso2, incrementalNodes, true);
        }
    }

    private async performTranslationRun(targetLangIso2: string, nodes: Text[], incremental: boolean): Promise<void> {
        const runId = ++this.activeRunId;
        const scopedNodes = incremental ? this.getConnectedUniqueNodes(nodes) : this.getConnectedUniqueNodes(Array.from(this.domNodes));

        if (scopedNodes.length === 0 && targetLangIso2 !== this.originalLanguageIso2) {
            return;
        }

        this.state.loading = true;
        this.isTranslating = true;
        let completed = false;

        try {
            this.cleanupDisconnectedNodes();

            if (targetLangIso2 === this.originalLanguageIso2) {
                this.restoreOriginalText(scopedNodes);
            } else {
                await this.doTranslation(targetLangIso2, scopedNodes, runId);
            }

            completed = runId === this.activeRunId;
        } catch (error) {
            if (!(error instanceof Error && error.name === 'AbortError')) {
                console.error('Translation run failed:', error);
            }
        } finally {
            this.isTranslating = false;
            this.state.loading = false;
        }

        if (!completed) {
            await this.processQueuedTranslations();
            return;
        }

        if (!incremental) {
            this.currentLanguageIso2 = targetLangIso2;
            this.state.selectedLang = this.state.languages.find((language) => language.iso_2 === targetLangIso2) ?? this.state.languages[0] ?? null;

            await this.store.set('CURRENT_LANGUAGE_ISO2', targetLangIso2);
        }

        await this.processQueuedTranslations();
    }

    private cleanupDisconnectedNodes(): void {
        const disconnectedNodes: Text[] = [];

        this.domNodes.forEach((node) => {
            if (!node.isConnected) {
                disconnectedNodes.push(node);
            }
        });

        disconnectedNodes.forEach((node) => {
            this.domNodes.delete(node);
            this.nodeToIdMap.delete(node);
            this.pendingIncrementalNodes.delete(node);
        });
    }

    private isExcludedBlockNode(node: Text): boolean {
        const selectors = this.projectConfig?.excluded_blocks ?? [];
        const parentElement = node.parentElement;

        if (!parentElement || selectors.length === 0) {
            return false;
        }

        return selectors.some((selector) => {
            try {
                return Boolean(parentElement.closest(selector));
            } catch {
                return false;
            }
        });
    }

    private async doTranslation(targetLangIso2: string, nodes: Text[], runId: number): Promise<void> {
        if (!this.projectConfig || !this.originalLanguageIso2) {
            return;
        }

        this.nodeToIdMap.clear();
        this.idToNodeMap.clear();

        const cachedTranslations: { id: number; translated: string }[] = [];
        const itemsToTranslate: { id: number; text: string }[] = [];

        nodes.forEach((node) => {
            const metadata = this.nodeRegistry.get(node);
            if (!metadata) {
                return;
            }

            this.nodeToIdMap.set(node, metadata.translation_id);
            this.idToNodeMap.set(metadata.translation_id, node);

            const knownTranslation = metadata.language_translations.find((translation) => translation.language === targetLangIso2);
            if (knownTranslation?.translated) {
                cachedTranslations.push({
                    id: metadata.translation_id,
                    translated: knownTranslation.translated,
                });
                return;
            }

            if (this.projectConfig?.languages.some((language) => language.name === metadata.translation_original)) {
                return;
            }

            if (this.isExcludedBlockNode(node)) {
                return;
            }

            itemsToTranslate.push({
                id: metadata.translation_id,
                text: metadata.translation_original,
            });
        });

        if (cachedTranslations.length > 0) {
            this.applyTranslationBatch(cachedTranslations, targetLangIso2);
        }

        if (itemsToTranslate.length === 0) {
            return;
        }

        await this.translationManager.translateBatch(itemsToTranslate, this.originalLanguageIso2, targetLangIso2, (translations) => {
            if (runId !== this.activeRunId) {
                return;
            }

            this.applyTranslationBatch(translations, targetLangIso2);
        });
    }

    private applyTranslationBatch(translations: { id: number; translated: string }[], targetLangIso2: string): void {
        this.withObserverPaused(() => {
            translations.forEach((translation) => {
                const targetNode = this.idToNodeMap.get(Number(translation.id));
                if (!targetNode?.isConnected) {
                    return;
                }

                const metadata = this.nodeRegistry.get(targetNode);
                if (!metadata || metadata.translation_id !== Number(translation.id)) {
                    return;
                }

                const leadingWhitespace = metadata.translation_original.match(/^\s*/)?.[0] || '';
                const trailingWhitespace = metadata.translation_original.match(/\s*$/)?.[0] || '';
                const translatedText = `${leadingWhitespace}${translation.translated.trim()}${trailingWhitespace}`;

                this.nodeRegistry.updateTranslation(targetNode, targetLangIso2, translatedText);
                targetNode.textContent = translatedText;
            });
        });
    }

    private restoreOriginalText(nodes: Text[]): void {
        this.withObserverPaused(() => {
            nodes.forEach((node) => {
                const metadata = this.nodeRegistry.get(node);
                if (metadata?.translation_original) {
                    node.textContent = metadata.translation_original;
                }
            });
        });
    }

    private isValidForTranslation(text: string): boolean {
        const trimmed = text.trim();
        if (!trimmed) {
            return false;
        }

        const unquoted = trimmed.replace(/^["'`]|["'`]$/g, '');

        if (!/\p{L}/u.test(unquoted)) {
            return false;
        }

        if (unquoted.length <= 3 && !/^[a-z]{2,3}$/i.test(unquoted)) {
            return false;
        }

        if (/^\S+@\S+\.\S+$/.test(unquoted)) return false;
        if (/(https?|ftp):\/\//i.test(unquoted)) return false;
        if (/^www\./i.test(unquoted)) return false;
        if (unquoted.includes('://')) return false;

        const domainPattern = /^[a-z0-9-]+\.(com|org|net|io|co|dev|app|ai|tech|info|biz|edu|gov|mil|int|uk|us|ca|au|de|fr|jp|cn|in|br|ru|es|it|nl|se|no|dk|fi|pl|be|at|ch|cz|gr|pt|ie|nz)(\/|$)/i;
        if (domainPattern.test(unquoted)) return false;

        if (
            !unquoted.includes(' ') &&
            /\.(css|js|jsx|ts|tsx|json|xml|html|htm|php|asp|aspx|jsp|pdf|doc|docx|xls|xlsx|ppt|pptx|txt|md|csv|png|jpg|jpeg|gif|svg|webp|ico|bmp|tiff|woff|woff2|ttf|otf|eot|mp3|mp4|avi|mov|wmv|flv|webm|zip|rar|tar|gz|7z|exe|dmg|pkg|deb|rpm)$/i.test(
                unquoted,
            )
        ) {
            return false;
        }

        if (/^(\/|\.\/|\.\.\/)/i.test(unquoted)) return false;
        if (/^<[a-z!][\s\S]*>$/i.test(unquoted)) return false;
        if (/^\{\{.*\}\}$/.test(unquoted)) return false;
        if (/^\$\{.*\}$/.test(unquoted)) return false;
        if (/^<%.*%>$/.test(unquoted)) return false;

        const letterCount = (unquoted.match(/\p{L}/gu) || []).length;
        const specialCharCount = (unquoted.match(/[^a-z0-9\s\p{L}]/giu) || []).length;
        if (letterCount > 0 && specialCharCount / unquoted.length > 0.5) {
            return false;
        }

        if (/^[.#][\w-]+$/.test(unquoted)) return false;
        if (/^[\w-]+=/.test(unquoted)) return false;
        if (/^\[.*\]$/.test(unquoted)) return false;

        return true;
    }

    private extractFlatTextNodes(root: Node | Node[], includeTrackedNodes = false): Text[] {
        const textNodes: Text[] = [];
        const roots = Array.isArray(root) ? root : [root];

        roots.forEach((candidateRoot) => {
            if (candidateRoot.nodeType === Node.TEXT_NODE) {
                const textNode = candidateRoot as Text;
                const text = textNode.textContent ?? '';
                const parent = textNode.parentElement;

                if (
                    parent &&
                    this.isValidForTranslation(text) &&
                    !['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'CODE', 'PRE'].includes(parent.tagName) &&
                    (includeTrackedNodes || !this.domNodes.has(textNode))
                ) {
                    textNodes.push(textNode);
                }
            }

            const walker = document.createTreeWalker(candidateRoot, NodeFilter.SHOW_TEXT, {
                acceptNode: (node: Node) => {
                    const text = node.textContent ?? '';
                    const parent = (node as Text).parentElement;

                    if (!text.trim() || !parent) {
                        return NodeFilter.FILTER_SKIP;
                    }

                    if (!this.isValidForTranslation(text)) {
                        return NodeFilter.FILTER_SKIP;
                    }

                    if (['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEMPLATE', 'CODE', 'PRE'].includes(parent.tagName)) {
                        return NodeFilter.FILTER_SKIP;
                    }

                    return includeTrackedNodes || !this.domNodes.has(node as Text) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_SKIP;
                },
            });

            let node: Node | null;
            while ((node = walker.nextNode())) {
                textNodes.push(node as Text);
            }
        });

        textNodes.forEach((node) => {
            this.nodeRegistry.register(node, node.textContent || '', this.projectConfig?.languages || [], this.originalLanguageIso2 || '');
        });

        return textNodes;
    }

    private handleMutations(nodes: Node[]): void {
        nodes.forEach((node) => this.debouncePendingNodes.add(node));

        if (this.mutationDebounceTimer !== null) {
            clearTimeout(this.mutationDebounceTimer);
        }

        this.mutationDebounceTimer = window.setTimeout(async () => {
            this.mutationDebounceTimer = null;

            const nodesToProcess = Array.from(this.debouncePendingNodes);
            this.debouncePendingNodes.clear();

            const extractedNodes = this.extractFlatTextNodes(nodesToProcess, true);
            if (extractedNodes.length === 0) {
                return;
            }

            extractedNodes.forEach((node) => {
                this.domNodes.add(node);
                this.pendingIncrementalNodes.add(node);
            });

            if (this.currentLanguageIso2 && this.currentLanguageIso2 !== this.originalLanguageIso2 && !this.isTranslating) {
                await this.processQueuedTranslations();
            }
        }, 150);
    }

    private handleRemovedNodes(): void {
        this.cleanupDisconnectedNodes();
    }

    private getConnectedUniqueNodes(nodes: Text[]): Text[] {
        return Array.from(new Set(nodes.filter((node) => node?.isConnected)));
    }

    private withObserverPaused(callback: () => void): void {
        const shouldRestart = this.observerPauseDepth === 0;

        if (shouldRestart) {
            this.domObserver.stop();
        }

        this.observerPauseDepth += 1;

        try {
            callback();
        } finally {
            this.observerPauseDepth -= 1;

            if (shouldRestart && this.observerPauseDepth === 0) {
                this.domObserver.start();
            }
        }
    }
}
