import Cacheable from "./Cacheable.js";

export default class LocalStorageCacheable extends Cacheable {
    constructor({ ttlMs = null, storageKey } = {}) {
        super({ ttlMs });
        this._storageKey = storageKey || `${this.constructor.name}:cache`;
        this.#loadFromStorage();
    }

    // ---------- Persistence helpers ----------
    #loadFromStorage() {
        try {
            const raw = localStorage.getItem(this._storageKey);
            if (!raw) return;

            const parsed = JSON.parse(raw);
            if (!Array.isArray(parsed)) return;

            // import into in-memory Map (filters out expired)
            this._importEntries(parsed);

            // After import we may have dropped expired entries, so resave
            this.#saveToStorage();
        } catch (e) {
            console.warn("Failed to load cache from localStorage:", e);
        }
    }

    #saveToStorage() {
        try {
            const snapshot = this._exportEntries();
            localStorage.setItem(this._storageKey, JSON.stringify(snapshot));
        } catch (e) {
            console.warn("Failed to save cache to localStorage:", e);
        }
    }

    // ---------- Override mutating methods to persist ----------

    setCached(query, data, options = {}) {
        super.setCached(query, data, options);
        this.#saveToStorage();
    }

    clearCached(query, options = {}) {
        super.clearCached(query, options);
        this.#saveToStorage();
    }

    clearAll() {
        super.clearAll();
        try {
            localStorage.removeItem(this._storageKey);
        } catch (e) {
            console.warn("Failed to clear localStorage cache:", e);
        }
    }
}
