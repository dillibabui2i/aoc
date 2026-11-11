const path = require('path')
const { defineConfig } = require('vitest/config')
const vue = require('@vitejs/plugin-vue')

module.exports = defineConfig({
    plugins: [vue()],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js/vue'),
            // Same alias as webpack.mix.js, so services importing `route` from ziggy-js load in specs.
            'ziggy-js': path.resolve(__dirname, 'vendor/tightenco/ziggy'),
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/vue/**/*.spec.js'],
        setupFiles: ['resources/js/vue/tests/setup.js'],
        // @angels-of-care/ui ships `"type": "module"` with directory-style
        // re-exports (`export {...} from './components'`, no extension).
        // Left externalized, Vitest hands it to Node's native ESM loader,
        // which rejects directory imports outright — force Vite to
        // transform it like first-party code instead (same root cause as
        // the `fullySpecified: false` fix already applied in
        // webpack.mix.js for the production build).
        server: {
            deps: {
                inline: ['@angels-of-care/ui'],
            },
        },
    },
})
