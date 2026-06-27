import { describe, expect, it } from "vitest";
import { NodeRegistry } from "../../resources/sdk/utils/NodeRegistry";

const languages = [
    { id: 1, name: "English", iso_2: "en", flag: "" },
    { id: 2, name: "French", iso_2: "fr", flag: "" },
];

describe("NodeRegistry", () => {
    it("reuses the same translation id for the same semantic node", () => {
        document.body.innerHTML = `<div id="app"><span id="label">Hello</span></div>`;
        const registry = new NodeRegistry();
        const span = document.getElementById("label") as HTMLSpanElement;
        const firstTextNode = span.firstChild as Text;

        expect(registry.register(firstTextNode, "Hello", languages, "en")).toBe(true);

        const originalMetadata = registry.get(firstTextNode);
        expect(originalMetadata).toBeDefined();
        registry.updateTranslation(firstTextNode, "fr", "Bonjour");

        span.textContent = "Hello again";
        const replacementTextNode = span.firstChild as Text;

        expect(registry.register(replacementTextNode, "Hello again", languages, "en")).toBe(true);

        const replacementMetadata = registry.get(replacementTextNode);
        expect(replacementMetadata?.translation_id).toBe(originalMetadata?.translation_id);
        expect(replacementMetadata?.translation_original).toBe("Hello again");
        expect(replacementMetadata?.language_translations.find((translation) => translation.language === "fr")?.translated).toBeNull();
    });

    it("does not reset metadata when the current text matches a known translation", () => {
        document.body.innerHTML = `<div><span id="label">Hello</span></div>`;
        const registry = new NodeRegistry();
        const span = document.getElementById("label") as HTMLSpanElement;
        const firstTextNode = span.firstChild as Text;

        registry.register(firstTextNode, "Hello", languages, "en");
        registry.updateTranslation(firstTextNode, "fr", "Bonjour");

        span.textContent = "Bonjour";
        const translatedTextNode = span.firstChild as Text;

        expect(registry.register(translatedTextNode, "Bonjour", languages, "en")).toBe(false);
        expect(registry.get(translatedTextNode)?.translation_original).toBe("Hello");
    });
});
