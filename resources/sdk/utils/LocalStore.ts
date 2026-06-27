export class LocalStore {
    private prefix = 'WEBLEX_';

    set(key: string, value: unknown): void {
        try {
            localStorage.setItem(this.prefix + key, JSON.stringify(value));
        } catch (error) {
            console.warn('Weblex LocalStore write failed:', error);
        }
    }

    getSync<T = unknown>(key: string, fallback: T | null = null): T | null {
        let raw = localStorage.getItem(this.prefix + key);

        if (raw === null) {
            raw = localStorage.getItem(key);
        }

        if (raw === null) {
            return fallback;
        }

        try {
            return JSON.parse(raw) as T;
        } catch {
            return (raw ?? fallback) as T | null;
        }
    }

    async get<T = unknown>(key: string, fallback: T | null = null): Promise<T | null> {
        return this.getSync(key, fallback);
    }

    remove(key: string): void {
        localStorage.removeItem(this.prefix + key);
        localStorage.removeItem(key);
    }

    clear(): void {
        const keysToRemove: string[] = [];

        for (let index = 0; index < localStorage.length; index += 1) {
            const key = localStorage.key(index);
            if (key?.startsWith(this.prefix)) {
                keysToRemove.push(key);
            }
        }

        keysToRemove.forEach((key) => localStorage.removeItem(key));
    }
}
