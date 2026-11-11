export default class Cacheable {
    #cache = new Map();
    #defaultTtlMs;

    #resolveTtlMs(ttlMs) {
        // undefined means: use instance default
        const raw = ttlMs === undefined ? this.#defaultTtlMs : ttlMs;

        // null means: cache forever
        if (raw === null) return null;

        // Treat 0 (or negative) as: do not cache
        if (raw === 0) return 0;

        // Allow numeric strings coming from datasets/env/blade
        const n = typeof raw === 'string' ? Number(raw) : raw;
        if (typeof n !== 'number' || Number.isNaN(n)) return null;
        if (n <= 0) return 0;
        return n;
    }

    constructor({ ttlMs = null } = {}) {
        this.#defaultTtlMs = ttlMs;
    }

    // ---------- Key helpers ----------
    static serializeQuery(query) {
        if (typeof query === "string") {
            return Cacheable.#normalizeKey(query);
        }

        const params = new URLSearchParams();
        Object.keys(query || {})
            .sort()
            .forEach((k) => {
                const v = query[k];
                if (v === undefined || v === null) return;

                if (Array.isArray(v)) {
                    v.forEach((item) => params.append(k, String(item)));
                } else {
                    params.append(k, String(v));
                }
            });

        return Cacheable.#normalizeKey(params.toString());
    }

    static #normalizeKey(qs) {
        return qs.trim().replace(/^\?+/, "");
    }

    #buildKey(query, variant) {
        const qsKey = this.constructor.serializeQuery(query);
        return variant ? `${variant}|${qsKey}` : qsKey;
    }

    // ---------- Core cache API ----------
    hasCached(query, { variant } = {}) {
        const key = this.#buildKey(query, variant);
        const entry = this.#cache.get(key);
        if (!entry) return false;

        if (entry.expiresAt && entry.expiresAt <= Date.now()) {
            this.#cache.delete(key);
            return false;
        }
        return true;
    }

    getCached(query, { variant } = {}) {
        const key = this.#buildKey(query, variant);
        const entry = this.#cache.get(key);
        if (!entry) return undefined;

        if (entry.expiresAt && entry.expiresAt <= Date.now()) {
            this.#cache.delete(key);
            return undefined;
        }
        return entry.data;
    }

    setCached(query, data, { ttlMs, variant } = {}) {
        const key = this.#buildKey(query, variant);
        const resolvedTtlMs = this.#resolveTtlMs(ttlMs);

        // ttl=0 means "do not cache" (prevents accidental infinite caching)
        if (resolvedTtlMs === 0) {
            this.#cache.delete(key);
            return;
        }

        const expiresAt =
            typeof resolvedTtlMs === "number" && resolvedTtlMs > 0
                ? Date.now() + resolvedTtlMs
                : null; // null => cache forever

        this.#cache.set(key, { data, expiresAt });
    }

    clearCached(query, { variant } = {}) {
        const key = this.#buildKey(query, variant);
        this.#cache.delete(key);
    }

    clearAll() {
        this.#cache.clear();
    }

    async fetchWithCache(query, fetchFn, { forceRefresh = false, ttlMs, variant } = {}) {
        if (!forceRefresh && this.hasCached(query, { variant })) {
            return this.getCached(query, { variant });
        }

        const data = await fetchFn();
        // ttlMs: undefined -> uses default; null -> forever; 0 -> do not cache; string numbers -> coerced
        this.setCached(query, data, { ttlMs, variant });
        return data;
    }

    // ---------- "Protected" helpers for subclasses ----------

    /**
     * Export entries as plain JSON-serializable data.
     * Array<[key, { data, expiresAt }]>
     */
    _exportEntries() {
        return Array.from(this.#cache.entries());
    }

    /**
     * Import entries from plain data, skipping expired ones.
     */
    _importEntries(entries) {
        const now = Date.now();
        for (const [key, entry] of entries || []) {
            if (!entry || typeof entry !== "object") continue;

            const { data, expiresAt } = entry;
            if (expiresAt && expiresAt <= now) continue; // skip expired
            this.#cache.set(String(key), { data, expiresAt: expiresAt ?? null });
        }
    }
}
