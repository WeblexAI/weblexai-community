export enum TranslationQualityE {
    AUTOMATIC = 'automatic',
    MANUAL = 'manual',
}

export enum GlossaryRuleE {
    NEVER_TRANSLATED = 'Never translate',
    ALWAYS_TRANSLATED = 'Always translate',
}

export enum TranslationModelTypeE {
    NMT = 'NMT',
    LLM = 'LLM',
}

export enum TranslationAudienceE {
    GENERAL = 'GENERAL',
    TECHNICAL = 'TECHNICAL',
    NONTECHNICAL = 'NON-TECHNICAL',
}

export enum TranslationToneE {
    INFORMAL = 'INFORMAL',
    NEUTRAL = 'NEUTRAL',
    FORMAL = 'FORMAL',
}

export enum CollaboratorRoleE {
    OWNER = 'owner',
    MANAGER = 'manager',
    TRANSLATOR = 'translator',
    VIEWER = 'viewer',
}
