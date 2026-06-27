import { CollaboratorRoleE, GlossaryRuleE, TranslationAudienceE, TranslationModelTypeE, TranslationQualityE, TranslationToneE } from '@/enums';
import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
    role: CollaboratorRoleE | null;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
    component?: string;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    success: boolean;
    error: string | null;
    message: string;
    data: { [key]: value };
    project: ProjectI;
    full_url: string;
    marketing_url: string;
    dashboard_url: string;
    cdn_url: string;
};

export interface ModelI {
    id: number;
    uuid: string;
    is_active: boolean;
    createdBy?: User;
    created_by_id?: number;
    created_at: string;
    updated_at: string;
    languageSwitcherConfig?: LanguageSwitcherConfig;
    formatted_created_at: string;
}

export interface ExcludedBlockI extends ModelI {
    selector: string;
    description: string;
}

export interface LanguageSwitcherConfig extends ModelI {
    target_parent_selector: string;
    should_display_name: boolean;
    should_display_full_name: boolean;
    should_display_flag: boolean;
    size: number;
    should_open_on_hover: boolean;
    should_close_on_outside_click: boolean;
    should_show_by_device: boolean;
    preferred_device: 'desktop' | 'mobile';
    device_pixel_breakpoint: number;
    project_id: number;
    project: ProjectI;
}

export interface User extends ModelI {
    name: string;
    email: string;
    collaboratedProjects: ProjectI[];
    force_password_change: boolean;
}

export interface ProviderCredentialI extends ModelI {
    name: string;
    provider: string;
    provider_label: string;
    provider_type: TranslationModelTypeE;
    model?: string | null;
}

export interface CollaboratorPivotI extends ModelI {
    project_id: number;
    user_id: number;
    role: CollaboratorRoleE;
}

export type CollaboratorWithPivotI = User & {
    pivot: CollaboratorPivotI;
};

export interface PageI extends ModelI {
    project_id: number;
    project?: ProjectI;
    title: string;
    domain: string;
    origin: string;
    is_active: boolean;
    path: string;
    translations_count: number;
    total_translated_words?: number;
    manual_translated_words?: number;
    is_blacklisted: boolean;
    blacklisted_languages: number[];
}

export interface ProjectI extends ModelI {
    name: string;
    slug: string;
    user_id: number;
    user?: User;
    is_integrated: boolean;
    should_display_automatics: boolean;
    original_language_id: number;
    pinged_at: string;
    original_language: LanguageI | null;
    languages: LanguageI[];
    firstLanguage?: LanguageI | null;
    first_language?: LanguageI | null;
    languages_count?: number;
    accepted_origins?: ProjectAcceptedOriginI[];
    translations_sum_total_words?: number;
    provider_credential_id?: number | null;
    provider_credential?: ProviderCredentialI | null;
    website_description?: string | null;
    translation_tone?: TranslationToneE | null;
    translation_audience?: TranslationAudienceE | null;
}

export interface ProjectAcceptedOriginI extends ModelI {
    project_id: number;
    origin: string;
    normalized_origin: string;
}

export interface ProjectLanguagePivot extends ModelI {
    project_id: number;
    language_id: number;
    is_public: boolean;
    should_display_automatics: boolean;
    is_disabled: boolean;
}

export interface LanguageI extends ModelI {
    name: string;
    country_name: string;
    color: string;
    iso_2: string;
    iso_3: string;
    flag?: MediaI;
    flag_url?: string;
    manual_translated_words?: number;
    total_translated_words?: number;
    translations_count?: number;
    pivot?: ProjectLanguagePivot;
}

export interface GlossaryI extends ModelI {
    project_id: number;
    project: ProjectI;
    languages: LanguageI[];
    text: string;
    translated: string;
    placeholder: string;
    is_case_sensitive: boolean;
    is_all_languages: boolean;
    rule: GlossaryRuleE;
}

export interface TranslationI extends ModelI {
    page_id: number;
    project_id: number;
    text: string;
    translated: string;
    type: string;
    attr: string;
    source_lang_id: string;
    target_lang_id: string;
    total_words: string;
    is_original: boolean;
    is_on: boolean;
    is_reviewed: boolean;
    quality: TranslationQualityE;
    last_used_at?: string | null;
}

export interface TranslationUsageI {
    id: number;
    text: string;
    text_preview: string;
    translated: string;
    translated_preview: string;
    quality: string;
    is_on: boolean;
    is_reviewed: boolean;
    total_words: number;
    last_used_at: string | null;
    page: PageI | null;
    language: LanguageI | null;
    manage_url: string | null;
}

export interface MediaI {
    id: number;
    model_type: string;
    model_id: number;
    uuid: string;
    collection_name: string;
    name: string;
    file_name: string;
    mime_type: string;
    size: number;
    generated_conversions: [];
    order_column: 1;
    created_at: string;
    updated_at: string;
    url: string;
    original_url: string;
}

export interface PaginationLinkI {
    active: boolean;
    label: string;
    url?: string | null;
}

export interface PaginatedDataI<DT> {
    current_page: number;
    data: DT[];
    first_page_url: string;
    from: number;
    last_page: number;
    last_page_url: string;
    links: PaginationLinkI[];
    next_page_url: string;
    path: string;
    per_page: number;
    prev_page_url: string;
    to: number;
    total: number;
}

export interface ActivityI extends ModelI {
    log_name: string;
    description: string;
    subject: object;
    project_id: number;
    formated_created_at: string;
    causer: User;
    properties: [];
    event: 'created' | 'updated' | 'deleted' | null;
}

export type InputSelectOption = {
    label: string;
    value: number | string;
};
