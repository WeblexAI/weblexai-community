import type { LanguageI } from '../types';

export interface NodeMetadata {
    translation_id: number;
    translation_key: string;
    translation_original: string;
    language_translations: { language: string; translated: string | null }[];
}

export class NodeRegistry {
    private registry = new WeakMap<Text, NodeMetadata>();
    private metadataByKey = new Map<string, NodeMetadata>();
    private idCounter = 1;

    get(node: Text): NodeMetadata | undefined {
        return this.registry.get(node);
    }

    register(node: Text, newText: string, languages: LanguageI[], currentLangIso: string): boolean {
        if (!node?.isConnected) {
            return false;
        }

        const key = this.getNodeKey(node);
        const existing = this.registry.get(node) ?? this.metadataByKey.get(key);

        if (!existing) {
            const metadata = this.createMetadata(key, newText, languages, currentLangIso);
            this.registry.set(node, metadata);
            this.metadataByKey.set(key, metadata);
            return true;
        }

        existing.translation_key = key;
        this.registry.set(node, existing);
        this.metadataByKey.set(key, existing);

        if (existing.translation_original === newText) {
            return false;
        }

        const knownTranslation = existing.language_translations.find((translation) => translation.translated === newText);
        if (knownTranslation) {
            return false;
        }

        existing.translation_original = newText;
        existing.language_translations = languages.map((language) => ({
            language: language.iso_2,
            translated: language.iso_2 === currentLangIso ? newText : null,
        }));

        return true;
    }

    updateTranslation(node: Text, targetLang: string, translatedText: string): boolean {
        const metadata = this.get(node);
        if (!metadata) {
            return false;
        }

        const existing = metadata.language_translations.find((translation) => translation.language === targetLang);
        if (existing) {
            existing.translated = translatedText;
            return true;
        }

        metadata.language_translations.push({
            language: targetLang,
            translated: translatedText,
        });

        return true;
    }

    private createMetadata(key: string, originalText: string, languages: LanguageI[], currentLangIso: string): NodeMetadata {
        return {
            translation_id: this.idCounter++,
            translation_key: key,
            translation_original: originalText,
            language_translations: languages.map((language) => ({
                language: language.iso_2,
                translated: language.iso_2 === currentLangIso ? originalText : null,
            })),
        };
    }

    private getNodeKey(node: Text): string {
        const segments: string[] = [];
        let element: Element | null = node.parentElement;

        while (element && element !== document.body) {
            const tag = element.tagName.toLowerCase();
            const id = element.id ? `#${element.id}` : '';
            const classes = element.classList.length > 0 ? `.${Array.from(element.classList).slice(0, 2).join('.')}` : '';
            const position = this.getElementIndex(element);
            segments.unshift(`${tag}${id}${classes}[${position}]`);
            element = element.parentElement;
        }

        const textIndex = this.getTextNodeIndex(node);
        return `${segments.join('>') || 'body'}::text[${textIndex}]`;
    }

    private getElementIndex(element: Element): number {
        if (!element.parentElement) {
            return 0;
        }

        return Array.from(element.parentElement.children).indexOf(element);
    }

    private getTextNodeIndex(node: Text): number {
        if (!node.parentNode) {
            return 0;
        }

        return Array.from(node.parentNode.childNodes)
            .filter((child) => child.nodeType === Node.TEXT_NODE)
            .indexOf(node);
    }
}
