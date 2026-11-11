# Vue.js Guide (aoc-map-tool)

Technical documentation for the Vue 3 frontend used by the newer, non-legacy parts of this project (`app/VBC`, `app/PrivateAPI`, etc.). For backend patterns of those modules, see their own `CLAUDE.md` files (e.g. `app/VBC/CLAUDE.md`).

## Module Overview
`resources/js/vue` is **not a SPA**: each Blade page mounts its own isolated Vue "micro-app" onto a single DOM element, all sharing one bundle, one Pinia instance, and one axios client. No vue-router. No TypeScript — plain JavaScript with `<script setup>`.

## Build
Bundled by **Laravel Mix** (webpack), not Vite:
```js
// webpack.mix.js
mix.js('resources/js/vue/app.js', 'public/js/vue-app.js').vue()
mix.alias({ '@': path.resolve(__dirname, 'resources/js/vue') })
```
`@/` resolves to `resources/js/vue`. Blade views load the bundle via `{{ mix('js/vue-app.js') }}`.

```sh
npm run lint         # eslint-plugin-vue, scoped to resources/js/vue
npm run lint:fix
npm run format        # prettier — no semicolons, single quotes, tabWidth 4
```

## Module Structure
```
resources/js/vue/
├── app.js                        # single entry point, auto-discovers modules
├── bootstrap.js
├── core/
│   └── api/
│       └── client.js             # shared axios instance (CSRF, 401/419 handling)
└── modules/
    └── <feature>/                # e.g. vbc
        ├── mount.js               # exports [{selector, component}, ...]
        ├── components/            # reusable/dumb components
        ├── sections/              # page-level "smart" components
        ├── composables/           # use*.js
        ├── config/                # static option lists / field metadata
        ├── services/              # thin API wrappers (*.service.js)
        └── store/                 # Pinia stores (*.store.js)
```

## Key Patterns

**Bootstrap/mounting** — `app.js` creates one Pinia instance and uses webpack's `require.context` to auto-discover every `modules/**/mount.js`. Each exports an array of `{ selector, component }` pairs; `app.js` mounts a fresh Vue app on each matching DOM element found on the page:
```js
// app.js
const pinia = createPinia()
const mountFiles = require.context('./modules', true, /mount\.js$/)
mountFiles.keys().forEach((path) => {
    mountFiles(path).default.forEach(({ selector, component }) => {
        const el = document.querySelector(selector)
        if (!el) return
        createApp(component).use(pinia).mount(el)
    })
})
```
**Adding a new module only requires a new `modules/<name>/mount.js`** — never edit `app.js`.

**Components** — Composition API only, always `<script setup>`. No Options API.

**Naming** — Components: PascalCase filenames (`HospitalizationFilters.vue`). Non-component JS: camelCase with a type suffix — `*.service.js`, `*.store.js`, `mount.js`. Composables: camelCase, `use` prefix (`usePermissions.js`). Config files: camelCase with a descriptive suffix (`hospitalizationTrackerFields.js`); constants inside are UPPER_SNAKE_CASE. Folders: lowercase (`components`, `sections`, `services`, `store`, `composables`, `config`).

**State (Pinia)** — Setup-store style (function returning refs/computed/actions), not the options-store style:
```js
export const useAllHospitalization = defineStore('allHospitalization', () => {
    const list = ref([])
    const loading = ref(false)
    // ...
    return { list, loading, fetchAll, setPage }
})
```
Store IDs are camelCase, matching the exported `useXxx` hook name.

**API layer** — One shared axios instance in `core/api/client.js` (CSRF token from `<meta name="csrf-token">`, redirects on 401, reloads on 419). Each module wraps its endpoints in a `services/*.service.js` that returns `r.data`:
```js
import client from '@/core/api/client.js'
export const AllHospitalizationService = {
    getAll: (params) => client.get('/vbc/hospitalization/data', { params }).then((r) => r.data),
}
```
**Layering is strict**: components call store actions → store actions call the service → service calls axios. Never skip a layer (no axios calls in components, no service calls in components).

**Routing** — None. If a feature needs multiple views, prefer separate Blade pages each mounting their own module, not vue-router.

## Third-party UI
`@angels-of-care/ui` provides shared components (`UiSelect`, `UiInput`, `UiButton`, `UiTable`, `UiModal`, `UiDateTimepicker`, etc.) and is babel-loaded via a custom webpack rule. Prefer these over building new base components.

## Testing
Vitest + `@vue/test-utils` + `@pinia/testing`, jsdom environment. Config: `vitest.config.js` (root), shares the `@/` → `resources/js/vue` alias with webpack.

```sh
npm run test          # vitest run
npm run test:watch    # vitest (watch mode)
```

Spec files live next to the code they test as `*.spec.js` (e.g. `filters.store.spec.js`, `HospitalizationFilters.spec.js`). Mock services with `vi.mock(...)` when testing stores; use `createTestingPinia()` when testing components so store actions are spies by default.

## Code Standards
ESLint (`eslint-plugin-vue` `flat/recommended` + `@eslint/js` recommended + `@vue/eslint-config-prettier`) and Prettier (no semicolons, single quotes, 4-space indent). Run `npm run lint` and `npm run format` before committing.

Notable rule choices in `eslint.config.js`:
- `vue/multi-word-component-names` — off (single-word component names allowed).
- `vue/component-api-style` — only `script-setup` is allowed, no Options API.
- `vue/block-order` — enforces `template` → `script` → `style`.
- `vue/no-unused-refs`, `vue/require-default-prop` — enforced.
- `require` is declared as a global (webpack's `require.context` is used in `bootstrap.js` for module auto-discovery).

## Best Practices

**`<script setup>` internal order** — imports, then `defineProps`/`defineEmits`, then store/composable instantiation, then `computed`, then local `ref`s that derive from the computed values, then `watch`, then plain functions. See `HospitalizationTracker/LoggedField.vue` for a canonical example of this order.

**Props**
- Always the object form with explicit `type`, never the bare-array shorthand: `defineProps({ field: { type: Object, required: true } })`.
- `vue/require-default-prop` is enforced — non-required props must declare a `default`.
- When a prop is a loosely-typed object (`type: Object`), document its expected shape with an inline comment next to the prop, e.g. `field: { type: Object, required: true }, // { key, label, type, options, logUpdates }`. Don't invent a formal shape/interface for it — there's no TypeScript in this codebase.
- Never mutate a prop. If a component needs an editable local copy, mirror it into a `ref` (a "draft" pattern) and keep it in sync with a `watch` on the source, as `LoggedField.vue` does with `draft`/`committedValue`.

**State ownership: store vs. props/emits** — Within a feature module, prefer having components read/write a shared Pinia store directly (`useHospitalizationTracker()`) rather than threading data down via props and back up via emits through several layers. Reserve props/emits for genuinely reusable, presentational components that have no business being aware of a specific store (`ToggleSwitch.vue`, `TrackerTabs.vue` use `v-model`/emit because they're generic UI, not feature state).

**Templates**
- Use `v-if`/`v-else-if`/`v-else` chains for type-driven rendering rather than a computed component map, when the branches are simple markup (see `LoggedField.vue`'s field-type switch).
- Always key `v-for` on a stable id/value, never on the array index.
- Keep template logic to simple expressions; push anything non-trivial into a `computed` or function in `<script setup>`.
- Prefer composing a screen out of small child components with named `<template #slot>` sections over one large template with many conditionals (`HospitalizationTrackerModal.vue`).

**Reactivity**
- Prefer `computed` for derived, read-only values; only fall back to a `watch` when you need a side effect (syncing a draft, calling a store action, etc.), not to "recompute" something a `computed` could do.
- Keep `watch` callbacks small and single-purpose — one watcher per concern rather than one watcher branching on multiple conditions.

**CSS**
- `<style scoped>` by default. BEM-style class names: `.block__element--modifier` (`.logged-field__actions`, `.tracker-modal__open`).
- Use `:deep()` only to reach into third-party (`@angels-of-care/ui`) internals, and only as a last resort — leave a comment explaining why the override exists and that it's a temporary hack if it's meant to be upstreamed.
- An unscoped second `<style>` block is acceptable only for content that Vue's scoping can't reach (e.g. teleported modal markup) — scope it manually by qualifying selectors with a unique class passed to the child (`css-class="tracker-modal"`), and leave a comment explaining why scoped styles don't apply here.

**Comments** — Don't narrate what the code does. Do comment: non-obvious business rules, especially with a ticket/AC reference (`// Auto-save path (AC31): non-logged fields persist on every change.`), and any deliberate workaround (temporary CSS override, stubbed composable) with what the real fix should eventually be.

**Composables/services that are stubs** — If a composable or service is a temporary placeholder ahead of a real backend/permissions system (see `usePermissions.js`), keep its public shape (function signature, return type) stable so callers don't need to change when it's implemented for real, and say so in a comment.
