import { afterEach, beforeEach, vi } from "vitest";

function createMemoryStorage() {
    const store = new Map<string, string>();

    return {
        get length() {
            return store.size;
        },
        clear() {
            store.clear();
        },
        getItem(key: string) {
            return store.has(key) ? store.get(key)! : null;
        },
        key(index: number) {
            return Array.from(store.keys())[index] ?? null;
        },
        removeItem(key: string) {
            store.delete(key);
        },
        setItem(key: string, value: string) {
            store.set(key, value);
        },
    } satisfies Storage;
}

beforeEach(() => {
    window.WeblexAIConfig = undefined;
    Object.defineProperty(window, "localStorage", {
        value: createMemoryStorage(),
        configurable: true,
    });

    document.body.innerHTML = "";
    vi.restoreAllMocks();
});

afterEach(() => {
    document.body.innerHTML = "";
});
