## Module Overview
`resources/js/vue` is the Vue 3 frontend for the newer, non-legacy parts of the project (see `app/VBC`, `app/PrivateAPI`, etc. for their backend counterparts). It is **not a SPA**: each Blade page mounts its own isolated Vue "micro-app" onto a single DOM element, all sharing one bundle, one Pinia instance, and one axios client. No vue-router. No TypeScript — plain JavaScript with `<script setup>`.

## Build
Bundled by **Laravel Mix** (webpack), not Vite:
```js
// webpack.mix.js
mix.js('resources/js/vue/app.js', 'public/js/vue-app.js').vue()
mix.alias({ '@': path.resolve(__dirname, 'resources/js/vue') })
```
`@/` resolves to `resources/js/vue`. Blade views load the bundle via `{{ mix('js/vue-app.js') }}`.

```sh
npm run lint        # eslint-plugin-vue, scoped to resources/js/vue
npm run lint:fix
npm run format       # prettier — no semicolons, single quotes, tabWidth 4
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
    └── <feature>/
        ├── mount.js               # exports [{selector, component}, ...]
        ├── components/            # reusable/dumb components
        ├── sections/              # page-level "smart" components
        ├── services/              # thin API wrappers (*.service.js)
        ├── store/                 # Pinia stores (*.store.js)
        └── styles/                # module stylesheet (only where global CSS is needed)
```

## Key Patterns

**Bootstrap/mounting** — `app.js` creates one Pinia instance and uses webpack's `require.context` to auto-discover every `modules/**/mount.js`. Each exports an array of `{ selector, component }` pairs; `app.js` mounts a fresh Vue app on each matching DOM element found on the page. **Adding a new module only requires a new `modules/<name>/mount.js`** — never edit `app.js`.

**Components** — Composition API only, always `<script setup>`. No Options API.

**Naming** — Components: PascalCase filenames (`HospitalizationFilters.vue`). Non-component JS: camelCase with a type suffix — `*.service.js`, `*.store.js`, `mount.js`. Folders: lowercase (`components`, `sections`, `services`, `store`).

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

**Permissions** — None on this side, by design. Access is enforced entirely by the backend
(`app/VBC`'s `vbc.permission` middleware against `config/permissions.php` — see `app/VBC/CLAUDE.md`'s
Auth section). Don't add a client-side permission check or gate a UI element's visibility on one — a
403 from the backend is the actual enforcement; a hidden button is not. (An earlier `useHasPermission()`
always-true stub existed here and was removed for exactly this reason — it looked like a real check and
wasn't one.)

**Styles** — Layout that belongs to one component stays in its own `<style scoped>`. Anything shared, and
anything that has to reach a teleported `UiModal` (its markup lands in `<body>`, outside every scoped
subtree), goes into the module's stylesheet — `modules/vbc/styles/vbc.scss`, imported **once** from
`mount.js` so it is compiled into the bundle a single time. Three files:

- `vbc.scss` — the only file with selectors;
- `_tokens.scss` — colours and sizes; component `<style scoped>` blocks `@use` it too, instead of
  repeating hex literals;
- `_mixins.scss` — blocks of design-system overrides, because the same block has to be re-emitted under
  more than one root.

Global rules are nested under one of three roots, and the root class is **doubled** — see Third-party UI
for why:

| Root | Where it comes from |
|---|---|
| `.v-app.v-app` | the container the Blade views wrap Vue mounts in |
| `.vbc-modal.vbc-modal` | passed to `UiModal` via its `css-class` prop |
| `.vbc-filters.vbc-filters` | the filter bars |

## Required-field validation (strict rule, whole VBC module)

Every required-field validation error, in every VBC form/modal, must:

1. **Name the field** in its message — `"Diagnosis Code is required."`, `"Rank is required."` — never
   the generic `"This field is required."` Mirror the field's own visible label text (or the shared
   label both the field and its backend request attribute use, e.g. `"Phone Number or E-mail is
   required."` for a jointly-required pair). See `ContactDetailsModal.vue`/`DiagnosisDetailsModal.vue`
   for the pattern: `:error="errors.icd_10_code ? 'Diagnosis Code is required.' : null"`.
2. **Turn the field's border red** — this already happens automatically once `:error` (or the design
   system's own `required` prop) is set to a non-null string; nothing extra to do here beyond (1).

This is enforced by `stacked-fields`'s error-state rules in `modules/vbc/styles/_mixins.scss` (label
color) plus the design system's own `.ui-*-wrap--error` styling (border/message color) — but neither of
those fixes a message that never named the field to begin with. When adding a new required field to any
VBC modal, write the message text out explicitly; don't reuse a placeholder string across fields.

(Several older modals — SickPlan, Care Coordination, Education, Notes, Follow-up — still have generic
`"This field is required."` messages predating this rule. Don't copy them as a pattern; fix forward.)

## Third-party UI
`@angels-of-care/ui` provides shared components (`UiSelect`, `UiInput`, `UiButton`, `UiTable`, etc.) and is babel-loaded via a custom webpack rule. Prefer these over building new base components.

The package has a single owner: never edit it, override from the outside. Its styles are scoped, so its
rules compile to `.ui-btn[data-v-hash]` — a class plus an attribute, two units of specificity. A
single-class ancestor only ties with that and the winner comes down to stylesheet order, which is why the
root class is doubled. The same trap applies to `:deep()`: a bare `:deep(.ui-select)` compiles to
`[data-v-x] .ui-select`, another tie — prefix it with the component's own class
(`.my-field :deep(.ui-select)`).

Two of its wrappers carry no layout of their own (`.ui-input-wrap`, `.ui-select-wrap`): label and control
only stack by accident in a narrow column, so anywhere fields are laid out on a grid the stacking has to
be explicit — that is what the `stacked-fields` mixin is for.

`stacked-fields` moves `UiDateTimepicker`'s visible border from `.ui-datepicker__row` (where the package
puts it) onto `.ui-datepicker__input`, so the label can sit above the field instead of beside it. Any
future override that resets `.ui-datepicker__row`'s frame must also repaint
`.ui-datepicker__row--error .ui-datepicker__input` — otherwise the package's own `error`/`required` red
border becomes invisible even though the field still passes `:error` correctly and the message still
renders underneath. This bit every `UiDateTimepicker` under `.vbc-modal` at once (Admission Date/Time,
Discharge Date, etc.) since they all go through the same mixin.

## Testing
Vitest + `@vue/test-utils` + `@pinia/testing`, jsdom environment. Config: `vitest.config.js` (root), shares the `@/` → `resources/js/vue` alias with webpack.

```sh
npm run test         # vitest run
npm run test:watch   # vitest (watch mode)
```

Spec files live under `resources/js/vue/tests/`, mirroring the path of the file they test relative to `resources/js/vue` (e.g. `modules/vbc/store/filters.store.js` → `tests/modules/vbc/store/filters.store.spec.js`). Import the file under test via the `@/` alias (not a relative `./` import, since the spec no longer lives next to it). Mock services with `vi.mock(...)` when testing stores; use `createTestingPinia()` when testing components so store actions are spies by default.

## Code Standards
ESLint (`eslint-plugin-vue` `flat/recommended` + `@eslint/js` recommended + `@vue/eslint-config-prettier`) and Prettier (no semicolons, single quotes, 4-space indent). Run `npm run lint` and `npm run format` before committing.

Notable rule choices in `eslint.config.js`:
- `vue/multi-word-component-names` — off (single-word component names allowed).
- `vue/component-api-style` — only `script-setup` is allowed, no Options API.
- `vue/block-order` — enforces `template` → `script` → `style`.
- `require` is declared as a global (webpack's `require.context` is used in `bootstrap.js` for module auto-discovery).
