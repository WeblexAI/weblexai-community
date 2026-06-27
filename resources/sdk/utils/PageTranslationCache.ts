import { LocalStore } from './LocalStore';

interface CachedTranslation {
    sourceText: string;
    translated: string;
    timestamp: number;
    ttl: number;
}

interface PageCache {
    [cacheKey: string]: CachedTranslation;
}

interface CacheStore {
    [pageScope: string]: PageCache;
}

export class PageTranslationCache {
    private static readonly CACHE_KEY = import.meta.env.VITE_CACHE_KEY || 'PAGE_TRANSLATION_CACHE';
    private static readonly CACHE_VERSION = 'v2';
    private static readonly DEFAULT_TTL = Number(import.meta.env.VITE_DEFAULT_TTL || 604800000);
    private static readonly MAX_CACHE_SIZE = Number(import.meta.env.VITE_MAX_CACHE_SIZE || 1000);

    private store = new LocalStore();
    private cache: CacheStore = {};
    private namespace = 'default';
    private ttl: number;

    constructor(ttl: number = PageTranslationCache.DEFAULT_TTL) {
        this.ttl = ttl;
        this.loadCache();
    }

    setNamespace(namespace: string): void {
        this.namespace = namespace || 'default';
    }

    get(text: string, targetLang: string): string | null {
        const pageCache = this.cache[this.getPageScope()];
        if (!pageCache) {
            return null;
        }

        const key = this.getCacheKey(text, targetLang);
        const entry = pageCache[key];
        if (!entry) {
            return null;
        }

        if (!this.isValid(entry)) {
            delete pageCache[key];
            this.saveCache();
            return null;
        }

        return entry.translated;
    }

    set(text: string, targetLang: string, translated: string): void {
        const pageScope = this.getPageScope();
        if (!this.cache[pageScope]) {
            this.cache[pageScope] = {};
        }

        const key = this.getCacheKey(text, targetLang);
        this.cache[pageScope][key] = {
            sourceText: text.trim(),
            translated,
            timestamp: Date.now(),
            ttl: this.ttl,
        };

        this.enforceCacheSizeLimit(pageScope);
        this.saveCache();
    }

    setMany(items: Array<{ text: string; targetLang: string; translated: string }>): void {
        const pageScope = this.getPageScope();
        if (!this.cache[pageScope]) {
            this.cache[pageScope] = {};
        }

        const now = Date.now();

        items.forEach((item) => {
            const key = this.getCacheKey(item.text, item.targetLang);
            this.cache[pageScope][key] = {
                sourceText: item.text.trim(),
                translated: item.translated,
                timestamp: now,
                ttl: this.ttl,
            };
        });

        this.enforceCacheSizeLimit(pageScope);
        this.saveCache();
    }

    has(text: string, targetLang: string): boolean {
        return this.get(text, targetLang) !== null;
    }

    getPageCache(targetLang: string): Map<string, string> {
        const result = new Map<string, string>();
        const pageCache = this.cache[this.getPageScope()];
        if (!pageCache) {
            return result;
        }

        Object.entries(pageCache).forEach(([key, entry]) => {
            if (!key.startsWith(`${targetLang}:`) || !this.isValid(entry)) {
                return;
            }

            result.set(entry.sourceText, entry.translated);
        });

        return result;
    }

    clearPage(): void {
        delete this.cache[this.getPageScope()];
        this.saveCache();
    }

    clearAll(): void {
        this.cache = {};
        this.saveCache();
    }

    getStats(): {
        totalPages: number;
        currentPageEntries: number;
        totalEntries: number;
        oldestEntry: number | null;
        newestEntry: number | null;
    } {
        const pageCache = this.cache[this.getPageScope()];
        const stats = {
            totalPages: Object.keys(this.cache).length,
            currentPageEntries: pageCache ? Object.keys(pageCache).length : 0,
            totalEntries: 0,
            oldestEntry: null as number | null,
            newestEntry: null as number | null,
        };

        Object.values(this.cache).forEach((entries) => {
            Object.values(entries).forEach((entry) => {
                stats.totalEntries += 1;

                if (stats.oldestEntry === null || entry.timestamp < stats.oldestEntry) {
                    stats.oldestEntry = entry.timestamp;
                }

                if (stats.newestEntry === null || entry.timestamp > stats.newestEntry) {
                    stats.newestEntry = entry.timestamp;
                }
            });
        });

        return stats;
    }

    setTTL(ttl: number): void {
        this.ttl = ttl;
    }

    private loadCache(): void {
        try {
            const stored = this.store.getSync<CacheStore>(PageTranslationCache.CACHE_KEY, {});
            this.cache = stored || {};
            this.cleanupExpiredEntries();
        } catch {
            this.cache = {};
        }
    }

    private saveCache(): void {
        this.store.set(PageTranslationCache.CACHE_KEY, this.cache);
    }

    private getPageScope(): string {
        return `${PageTranslationCache.CACHE_VERSION}:${this.namespace}:${this.normalizeUrl(window.location.href)}`;
    }

    private normalizeUrl(url: string): string {
        try {
            const parsed = new URL(url);
            const pathname = parsed.pathname.replace(/\/$/, '') || '/';
            return `${parsed.origin}${pathname}${parsed.search}${parsed.hash}`;
        } catch {
            return url;
        }
    }

    private getCacheKey(text: string, targetLang: string): string {
        return `${targetLang}:${this.hashText(text.trim())}`;
    }

    private hashText(text: string): string {
        let hash = 2166136261;

        for (let index = 0; index < text.length; index += 1) {
            hash ^= text.charCodeAt(index);
            hash = Math.imul(hash, 16777619);
        }

        return (hash >>> 0).toString(36);
    }

    private isValid(entry: CachedTranslation): boolean {
        return Date.now() - entry.timestamp < entry.ttl;
    }

    private cleanupExpiredEntries(): void {
        let removedEntries = 0;

        Object.entries(this.cache).forEach(([pageScope, pageCache]) => {
            Object.entries(pageCache).forEach(([cacheKey, entry]) => {
                if (!this.isValid(entry)) {
                    delete pageCache[cacheKey];
                    removedEntries += 1;
                }
            });

            if (Object.keys(pageCache).length === 0) {
                delete this.cache[pageScope];
            }
        });

        if (removedEntries > 0) {
            this.saveCache();
        }
    }

    private enforceCacheSizeLimit(pageScope: string): void {
        const currentPageCache = this.cache[pageScope];
        if (!currentPageCache) {
            return;
        }

        const entryCount = Object.keys(currentPageCache).length;
        if (entryCount <= PageTranslationCache.MAX_CACHE_SIZE) {
            return;
        }

        const newestEntries = Object.entries(currentPageCache)
            .sort(([, a], [, b]) => b.timestamp - a.timestamp)
            .slice(0, PageTranslationCache.MAX_CACHE_SIZE);

        this.cache[pageScope] = Object.fromEntries(newestEntries);
    }
}
