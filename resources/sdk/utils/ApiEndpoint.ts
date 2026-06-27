declare global {
    interface Window {
        WeblexAIConfig?: {
            apiKey?: string;
        };
    }
}

export function resolveApiEndpoint(): string {
    const currentScript = document.currentScript as HTMLScriptElement | null;
    const script = currentScript?.src ? currentScript : Array.from(document.scripts).find((item) => /\/wlai\/weblexai(?:\.min)?\.js(?:\?|$)/.test(item.src));

    if (script?.src) {
        return `${new URL(script.src).origin}/api/project`;
    }

    throw new Error('WeblexAI could not determine its API endpoint.');
}
