import WeblexAIEngine from './utils/WeblexEngine';

const engine = new WeblexAIEngine();

const WeblexAI = {
    init: (key: string) => engine.init(key),
    translateTo: (lang: string) => engine.translateTo(lang),
    clearCache: () => engine.clearCache(),
    getDebugSnapshot: () => engine.getDebugSnapshot(),
};

if (typeof window !== 'undefined') {
    (window as Window & { WeblexAI?: typeof WeblexAI }).WeblexAI = WeblexAI;

    const configuredApiKey = window.WeblexAIConfig?.apiKey;
    if (configuredApiKey) {
        void WeblexAI.init(configuredApiKey).catch((error) => {
            console.error('WeblexAI initialization failed:', error);
        });
    }
}

export default WeblexAI;
