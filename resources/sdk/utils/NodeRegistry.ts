import type { LanguageI, TranslationType } from '../types';

export interface NodeMetadata {
    translation_id: number;
    translation_key: string;
    translation_original: string;
    type: TranslationType;
    attr: string;
    context: string;
    language_translations: { language: string; translated: string | null }[];
}

export class NodeRegistry {
    private registry = new WeakMap<Node, Map<string, NodeMetadata>>();
    private metadataByKey = new Map<string, NodeMetadata>();
    private idCounter = 1;

    get(node: Node): NodeMetadata | undefined {
        return this.registry.get(node)?.values().next().value;
    }
    getTarget(node: Node, type: TranslationType, attr = ''): NodeMetadata | undefined {
        return this.registry.get(node)?.get(`${type}:${attr}`);
    }

    register(node: Text, text: string, languages: LanguageI[], currentLangIso: string): boolean {
        return this.registerTarget(node, text, languages, currentLangIso, 'text');
    }

    registerTarget(node: Node, text: string, languages: LanguageI[], currentLangIso: string, type: TranslationType, attr = '', context = ''): boolean {
        if (!node.isConnected) return false;
        const key = this.getNodeKey(node, type, attr, context);
        const targets = this.registry.get(node) ?? new Map<string, NodeMetadata>();
        const targetKey = `${type}:${attr}`;
        const existing = targets.get(targetKey) ?? this.metadataByKey.get(key);
        if (!existing) {
            const metadata: NodeMetadata = {
                translation_id: this.idCounter++,
                translation_key: key,
                translation_original: text,
                type,
                attr,
                context,
                language_translations: languages.map((language) => ({ language: language.iso_2, translated: language.iso_2 === currentLangIso ? text : null })),
            };
            targets.set(targetKey, metadata);
            this.registry.set(node, targets);
            this.metadataByKey.set(key, metadata);
            return true;
        }
        existing.translation_key = key;
        existing.type = type;
        existing.attr = attr;
        const contextChanged = existing.context !== context;
        existing.context = context;
        targets.set(targetKey, existing);
        this.registry.set(node, targets);
        this.metadataByKey.set(key, existing);
        if (!contextChanged && (existing.translation_original === text || existing.language_translations.some((translation) => translation.translated === text))) return false;
        if (existing.language_translations.some((translation) => translation.translated === text)) text = existing.translation_original;
        existing.translation_original = text;
        existing.language_translations = languages.map((language) => ({ language: language.iso_2, translated: language.iso_2 === currentLangIso ? text : null }));
        return true;
    }

    updateTranslation(node: Node, targetLang: string, translatedText: string): boolean {
        const metadata = this.get(node);
        if (!metadata) return false;
        const existing = metadata.language_translations.find((translation) => translation.language === targetLang);
        if (existing) existing.translated = translatedText;
        else metadata.language_translations.push({ language: targetLang, translated: translatedText });
        return true;
    }

    updateTargetTranslation(node: Node, type: TranslationType, attr: string, targetLang: string, translatedText: string): boolean {
        const metadata = this.getTarget(node, type, attr);
        if (!metadata) return false;
        const existing = metadata.language_translations.find((translation) => translation.language === targetLang);
        if (existing) existing.translated = translatedText;
        else metadata.language_translations.push({ language: targetLang, translated: translatedText });
        return true;
    }

    private getNodeKey(node: Node, type: TranslationType, attr: string, context: string): string {
        const element = node.nodeType === Node.TEXT_NODE ? node.parentElement : (node as Element);
        const segments: string[] = [];
        let current: Element | null = element;
        while (current && current !== document.body) {
            const position = current.parentElement ? Array.from(current.parentElement.children).indexOf(current) : 0;
            segments.unshift(`${current.tagName.toLowerCase()}#${current.id || ''}[${position}]`);
            current = current.parentElement;
        }
        const textIndex =
            node.nodeType === Node.TEXT_NODE && node.parentNode
                ? Array.from(node.parentNode.childNodes)
                      .filter((child) => child.nodeType === Node.TEXT_NODE)
                      .indexOf(node as ChildNode)
                : 0;
        return `${segments.join('>') || 'body'}:${type}:${attr}:${textIndex}:${context}`;
    }
}
