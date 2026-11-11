import { vi } from 'vitest'

// Ziggy's route() needs the server-rendered `Ziggy` route list, which does
// not exist under jsdom. Resolve route names to plain URLs instead; specs can
// still override this with their own vi.mock('ziggy-js', ...).
vi.mock('ziggy-js', () => ({
    route: (name, params) => {
        if (params === undefined || params === null) {
            return `/${name}`
        }
        if (typeof params === 'object' && !Array.isArray(params)) {
            const query = Object.entries(params)
                .map(([key, value]) => `${key}=${value}`)
                .join('&')
            return `/${name}?${query}`
        }
        return `/${name}/${[].concat(params).join('/')}`
    },
}))
