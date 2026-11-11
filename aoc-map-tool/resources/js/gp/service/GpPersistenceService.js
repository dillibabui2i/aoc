// GpPersistenceService.js
import LocalStorageCacheable from "./LocalStorageCacheable.js";

export default class GpPersistenceService extends LocalStorageCacheable {
    /**
     * @param {Object} routes
     * @param {Object} options - { ttlMs?, storageKey?, csrfToken? }
     */
    constructor(routes = {}, options = {}) {
        super(options);
        this.#routes = routes;
        this.#csrfToken = options.csrfToken || null;
        // optional overrides
        if (options.gpStoragePrefix) this.#gpStoragePrefix = String(options.gpStoragePrefix);
        if (options.gpIndexKey) this.#gpIndexKey = String(options.gpIndexKey);
    }

    #routes;
    #csrfToken;

    // In-memory GP snapshot store (for local testing without server)
    // NOTE: this resets on page reload.
    #gpMemoryStore = new Map(); // id -> snapshot
    #gpMemorySeq = 1;

    // Persistent GP snapshots (optional) - survives reload
    #gpStoragePrefix = 'gp:snapshot:';
    #gpIndexKey = 'gp:snapshots';

    // ----------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------

    #gpLsKey(id) {
        return `${this.#gpStoragePrefix}${String(id)}`;
    }

    #lsReadJson(key, fallback) {
        try {
            const raw = localStorage.getItem(key);
            if (!raw) return fallback;
            return JSON.parse(raw);
        } catch (e) {
            return fallback;
        }
    }

    #lsWriteJson(key, value) {
        try {
            localStorage.setItem(key, JSON.stringify(value));
            return true;
        } catch (e) {
            return false;
        }
    }

    #lsGetIndex() {
        return this.#lsReadJson(this.#gpIndexKey, []);
    }

    #lsSetIndex(ids) {
        this.#lsWriteJson(this.#gpIndexKey, Array.isArray(ids) ? ids : []);
    }

    #cloneSnapshot(obj) {
        return (typeof structuredClone === 'function')
            ? structuredClone(obj)
            : JSON.parse(JSON.stringify(obj));
    }

    #getRoute(key) {
        const route = this.#routes[key];
        if (!route) {
            throw new Error(`Route "${key}" is not defined in GpPersistenceService`);
        }
        return route;
    }

    #buildUrl(routeKey, query) {
        const base = this.#getRoute(routeKey);
        if (!query) return base;

        const qs = this.constructor.serializeQuery(query);
        return qs ? `${base}?${qs}` : base;
    }

    async #getWithCache(
        routeKey,
        query = {},
        { responseType = "json", forceRefresh = false, ttlMs = 60000 } = {}
    ) {
        const url = this.#buildUrl(routeKey, query);
        const keyQuery = { route: routeKey, ...query };

        return this.fetchWithCache(
            keyQuery,
            async () => {
                const res = await fetch(url, {
                    method: "GET",
                    credentials: "same-origin",
                });

                if (!res.ok) {
                    throw new Error(`GET ${url} failed with status ${res.status}`);
                }

                switch (responseType) {
                    case "json": return res.json();
                    case "html":
                    case "text": return res.text();
                    default: return res.text();
                }
            },
            { forceRefresh, ttlMs, variant: responseType }
        );
    }

    async #postUrlEncoded(
        routeKey,
        payload = {},
        { responseType = "json" } = {}
    ) {
        const url = this.#getRoute(routeKey);

        const body = new URLSearchParams();

        // inject csrf token if present
        if (this.#csrfToken) {
            body.set("_token", this.#csrfToken);
        }

        // payload data
        Object.entries(payload || {}).forEach(([k, v]) => {
            if (v !== undefined && v !== null) {
                body.set(k, String(v));
            }
        });

        const res = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            },
            body: body.toString(),
        });

        if (!res.ok) {
            throw new Error(`POST ${url} failed with status ${res.status}`);
        }

        switch (responseType) {
            case "json": return res.json();
            case "html":
            case "text": return res.text();
            default: return res.json();
        }
    }

    async #postForm(
        routeKey,
        formElement,
        extraBody = {},
        { responseType = "json" } = {}
    ) {
        const url = this.#getRoute(routeKey);

        const body = new URLSearchParams();

        // form fields
        if (formElement) {
            const formData = new FormData(formElement);
            for (const [name, value] of formData.entries()) {
                body.append(name, value);
            }
        }

        // csrf
        if (this.#csrfToken) {
            body.set("_token", this.#csrfToken);
        }

        // extra fields
        Object.entries(extraBody || {}).forEach(([k, v]) => {
            if (v !== undefined && v !== null) {
                body.set(k, String(v));
            }
        });

        const res = await fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
            },
            body: body.toString(),
        });

        if (!res.ok) {
            throw new Error(`POST ${url} failed with status ${res.status}`);
        }

        switch (responseType) {
            case "json": return res.json();
            case "html":
            case "text": return res.text();
            default: return res.text();
        }
    }

    // ----------------------------------------------------------
    // Public API
    // ----------------------------------------------------------

    /**
     * Save GP.
     */
    async saveGp(formOrSnapshot, extraBody = {}, options = { type: 'in-memory' }) {

        if (typeof formOrSnapshot !== 'object' ) {
            console.error('Snapshot format incorrect')
        }

        if (options.type === 'in-memory') {
            const id = String(options.id ?? this.#gpMemorySeq++);

            // defensive clone so callers can't mutate stored data by reference
            const snap = this.#cloneSnapshot(formOrSnapshot);

            // always keep in memory
            this.#gpMemoryStore.set(id, snap);

            // persist (survive reload) unless explicitly disabled
            const persist = options.persist !== false;
            if (persist && typeof localStorage !== 'undefined') {
                this.#lsWriteJson(this.#gpLsKey(id), snap);

                const ids = this.#lsGetIndex();
                if (!ids.includes(id)) ids.unshift(id);
                this.#lsSetIndex(ids);
            }

            return {
                status: true,
                id,
                data: snap,
                persisted: persist
            };
        }

        const calculator = (typeof formOrSnapshot === 'string')
            ? formOrSnapshot
            : JSON.stringify(formOrSnapshot);
        return this.#postUrlEncoded('saveGp', { calculator: calculator, id: options?.id }, {
            responseType: options.responseType || 'json',
        });


    }

    /**
     * Restore GP snapshot.
     * - prefers localStorage (survives reload)
     * - falls back to in-memory store
     */
    async restoreGp(id, restoreFrom = 'in-memory') {

        if (restoreFrom === 'in-memory') {
            const key = String(id);

            // 1) localStorage first
            if (typeof localStorage !== 'undefined') {
                const snapLs = this.#lsReadJson(this.#gpLsKey(key), null);
                if (snapLs) {
                    return {
                        status: true,
                        id: key,
                        data: this.#cloneSnapshot(snapLs),
                        source: 'localStorage'
                    };
                }
            }

            // 2) in-memory fallback
            if (!this.#gpMemoryStore.has(key)) {
                return {
                    status: false,
                    id: key,
                    data: null,
                    message: `No GP snapshot found for id=${key}`
                };
            }

            const snap = this.#gpMemoryStore.get(key);
        }

        return this.#getWithCache("getGp", {gpId: id}, {
            responseType: "json",
            forceRefresh: true,
            ttlMs: 3000,
        });

    }

    /** List saved snapshot ids (localStorage index). */
    listGpSnapshots() {
        if (typeof localStorage === 'undefined') return [];
        return this.#lsGetIndex();
    }

    /** Delete a saved snapshot (localStorage + memory). */
    deleteGpSnapshot(id) {
        const key = String(id);
        this.#gpMemoryStore.delete(key);

        if (typeof localStorage !== 'undefined') {
            try { localStorage.removeItem(this.#gpLsKey(key)); } catch (e) {}

            const ids = this.#lsGetIndex().filter(x => String(x) !== key);
            this.#lsSetIndex(ids);
        }

        return { status: true, id: key };
    }

    async loadPayerDropdown(query = {}, options = { ttlMs: 60000}) {
        return this.#getWithCache("loadPayerDropdown", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            ttlMs: options.ttlMs,
        });
    }

    async requestWholeHome(query = {aocPatientId: null, showPermOnly: true, permOnlyWeekStart : '', permOnlyWeekEnd: ''}, options = {ttlMs: 60000}) {
        return this.#getWithCache("requestWholeHome", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            ttlMs: 5000,
        });
    }

    async getScheduleClient(query = {}, options = {}) {
        return this.#getWithCache("getScheduleClient", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            ttlMs: options.ttlMs,
        });
    }

    /**
     * Autocomplete: fetch candidate/clinician suggestions.
     * Expected response: Array<{ label, value, ClinicianID, id, ... }>
     */
    async getCandidateName(query = {}, options = {}) {
        return this.#getWithCache("getCandidateName", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            // candidate autocomplete should be fresher than payer dropdown
            ttlMs: options.ttlMs ?? 30000,
        });
    }

    /**
     * API call to clear a GP instance on backend.
     * Also clears local cache by default.
     */
    async clear(payload = {}, options = {}) {
        const response = await this.#postUrlEncoded("clear", payload, {
            responseType: options.responseType || "json",
        });

        if (options.clearLocal !== false) {
            this.clearAll();
        }

        return response;
    }

    /**
     * Fetch service dropdown values (cached JSON)
     */
    async getService(query = {}, options = {}) {
        return this.#getWithCache("getService", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            // Service options are user-facing, can change with auth/payer rules,
            // and stale localStorage data can mask frontend fixes after deploy.
            ttlMs: options.ttlMs ?? 0,
        });
    }

    // Factory
    static create(routes, options = {}) {
        return new GpPersistenceService(routes, options);
    }

    /**
     * Request GP calculation by sending the calculator payload to backend.
     * No cache is used here.
     *
     * Expected backend: POST route key "calculate" with urlencoded field `calculator`.
     */
    async requestCalculation(payload = {}, options = {}) {
        const calculator = (typeof payload === 'string')
            ? payload
            : JSON.stringify(payload);
        return this.#postUrlEncoded('calculate', { calculator: calculator }, {
            responseType: options.responseType || 'json',
        });
    }

    async loadScheduledHours (query = {}, options = {}) {
        return this.#getWithCache("loadScheduledHours", query, {
            responseType: "json",
            forceRefresh: options.forceRefresh,
            ttlMs: options.ttlMs,
        });
    }
}
