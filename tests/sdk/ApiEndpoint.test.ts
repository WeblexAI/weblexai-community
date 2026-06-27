import { describe, expect, it } from "vitest";
import { resolveApiEndpoint } from "../../resources/sdk/utils/ApiEndpoint";

describe("resolveApiEndpoint", () => {
    it("derives the API host from the embedded script", () => {
        window.WeblexAIConfig = undefined;
        const script = document.createElement("script");
        script.src = "https://translate.example/wlai/weblexai.min.js";
        document.body.appendChild(script);

        expect(resolveApiEndpoint()).toBe("https://translate.example/api/project");
    });
});
