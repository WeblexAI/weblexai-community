import { describe, expect, it, vi } from "vitest";
import WeblexAIEngine from "../../resources/sdk/utils/WeblexEngine";

describe("WeblexAIEngine", () => {
    it("keeps the current language unchanged when a translation run fails", async () => {
        document.body.innerHTML = `<main><p>Hello world</p></main>`;

        const fetchMock = vi.fn()
            .mockResolvedValueOnce({
                ok: true,
                json: async () => ({
                    data: {
                        is_active: true,
                        original_language: { id: 1, name: "English", iso_2: "en", flag: "" },
                        languages: [
                            { id: 1, name: "English", iso_2: "en", flag: "" },
                            { id: 2, name: "French", iso_2: "fr", flag: "" },
                        ],
                        excluded_blocks: [],
                        page: "/",
                        hide_water_mark: false,
                        switcher_config: {
                            target_parent_selector: null,
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
                    },
                }),
            })
            .mockResolvedValueOnce({
                ok: false,
                status: 500,
                text: async () => "Failed",
            });

        vi.stubGlobal("fetch", fetchMock);

        const engine = new WeblexAIEngine("https://example.com/api/project");
        await engine.init("test-key");
        await engine.translateTo("fr");

        expect(engine.getDebugSnapshot().currentLanguageIso2).toBe("en");
        expect(engine.state.selectedLang?.iso_2).toBe("en");
    });
});
