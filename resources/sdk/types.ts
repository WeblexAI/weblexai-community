export interface LanguageI {
    id: number;
    name: string;
    iso_2: string;
    flag: string;
}

export interface ProjectConfigI {
    original_language: LanguageI;
    languages: LanguageI[];
    is_active: boolean;
    excluded_blocks: string[];
    page: string;
    hide_water_mark: boolean;
    switcher_config: {
        target_parent_selector: string | null;
        should_display_name: boolean;
        should_display_full_name: boolean;
        should_display_flag: boolean;
        size: number;
        should_open_on_hover: boolean;
        should_close_on_outside_click: boolean;
        should_show_by_device: boolean;
        preferred_device: 'desktop' | 'mobile' | null;
        device_pixel_breakpoint: number;
    };
}

export interface ProjectConfigResponseI {
    success: boolean;
    data: ProjectConfigI;
}

export interface TranslationRequestItemI {
    id: number;
    text: string;
}

export interface TranslationResponseItemI {
    id: number;
    translated: string;
}

export interface TranslationAPIResponseI {
    data: {
        translations: TranslationResponseItemI[];
    };
}

export interface StateI {
    loading: boolean;
    languages: LanguageI[];
    selectedLang: LanguageI | null;
}

export interface TranslationDebugSnapshotI {
    currentLanguageIso2: string | null;
    originalLanguageIso2: string | null;
    trackedNodeCount: number;
    pendingIncrementalNodeCount: number;
    cache: {
        totalPages: number;
        currentPageEntries: number;
        totalEntries: number;
        oldestEntry: number | null;
        newestEntry: number | null;
    };
}

export interface WeblexAIEnginePublicI {
    projectConfig: ProjectConfigI | null;
    state: StateI;
    init(apiKey: string): Promise<void>;
    translateTo(targetLangIso2: string, isInitialLoad?: boolean): Promise<void>;
    clearCache(): void;
    getDebugSnapshot(): TranslationDebugSnapshotI;
}
