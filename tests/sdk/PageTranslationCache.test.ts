import { describe, expect, it } from "vitest";
import { PageTranslationCache } from "../../resources/sdk/utils/PageTranslationCache";

describe("PageTranslationCache", () => {
    it("scopes translations by namespace", () => {
        window.history.replaceState({}, "", "/pricing?tab=a#faq");

        const projectACache = new PageTranslationCache(60_000);
        projectACache.setNamespace("project-a");
        projectACache.set("Hello", "fr", "Bonjour");

        const projectBCache = new PageTranslationCache(60_000);
        projectBCache.setNamespace("project-b");

        expect(projectACache.get("Hello", "fr")).toBe("Bonjour");
        expect(projectBCache.get("Hello", "fr")).toBeNull();
    });

    it("treats query and hash variants as different page scopes", () => {
        const cache = new PageTranslationCache(60_000);
        cache.setNamespace("project-a");

        window.history.replaceState({}, "", "/pricing?tab=a#faq");
        cache.set("Hello", "fr", "Bonjour");

        window.history.replaceState({}, "", "/pricing?tab=b#faq");
        expect(cache.get("Hello", "fr")).toBeNull();

        window.history.replaceState({}, "", "/pricing?tab=a#faq");
        expect(cache.get("Hello", "fr")).toBe("Bonjour");
    });
});
