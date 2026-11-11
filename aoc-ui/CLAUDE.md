# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`@angels-of-care/ui` — Vue 3 design system component library for Angels of Care. Distributed as raw source (no build step): `main` points to `src/index.js`, `style` to `src/styles/index.scss`. Consumers import components and compile SCSS themselves.

## Commands

- Install deps: `npm install`
- Run all tests: `npm test` (Vitest, single run)
- Watch tests: `npm run test:watch`
- Run a single test file: `npx vitest run src/components/UiButton/UiButton.test.js`
- Lint: `npm run lint` / autofix: `npm run lint:fix`
- Publish: pushing a `v*` tag (or manually running the "Publish Package" workflow) triggers `.github/workflows/publish.yml`, which sets the version from the tag and runs `npm publish` to `npm.pkg.github.com` (GitHub Packages, see `publishConfig` in `package.json`).

## Architecture

- `src/index.js` re-exports everything from `src/components/index.js`, which re-exports each component from its own folder. Every component lives in `src/components/<ComponentName>/` with two files: `<ComponentName>.vue` and `index.js` (`export {default as X} from './X.vue'`). Adding a component means: create the folder, add both files, then wire it into `src/components/index.js` and `src/index.js`.
- Styles are split between `src/styles/` (source: `_tokens.scss`, `_mixins.scss`, `index.scss` using `@forward`) and a root-level `styles/` folder that exists purely as a re-export shim (`@forward '../src/styles/index'`) for consumers who import `@angels-of-care/ui/styles`. When changing tokens/mixins, edit the files under `src/styles/`, not the root shim.
- Design tokens (colors, spacing, typography, radii, transitions) are SCSS variables in `src/styles/_tokens.scss`. Components `@use '../../styles/tokens' as *` and build BEM-style classes (`.ui-btn`, `.ui-btn--primary`, etc.) from these tokens rather than hardcoding values — follow this pattern for new/changed components (see `UiButton.vue`). Some older components (e.g. `UiModal`, `UiDateTimepicker`) still use hardcoded colors/plain CSS instead of tokens; don't propagate that pattern into new code.
- Components follow Vue 3 `<script setup>` with `defineProps`/`defineEmits`, and use `v-model` via `modelValue` + `update:modelValue` (e.g. `UiInput`, `UiModal`, `UiDateTimepicker`). Prop comments document allowed string enum values inline (e.g. `variant: primary | secondary | danger | ghost`) since there are no TS types or prop validators enforcing them.
- `UiDateTimepicker` wraps `flatpickr` (imported directly, with its CSS) and manages the underlying instance manually via `onMounted`/`onBeforeUnmount`/`watch` — follow this lifecycle pattern for any other third-party-widget wrapper.
- `UiModal` renders via `<teleport to="body">` and toggles `document.body.style.overflow` while open; its markup/class names follow Bootstrap's modal structure (`modal-backdrop`, `modal-dialog`, etc.) even though the project has no Bootstrap dependency — this is intentional for drop-in styling compatibility in consuming apps.

## Testing & linting

Tests use Vitest + `@vue/test-utils` (jsdom environment), colocated as `<Component>.test.js` next to each `.vue` file — see `UiButton.test.js` and `UiInput.test.js` for the pattern. Only these two components have tests so far; other components are untested.

ESLint uses the flat config in `eslint.config.js` with `eslint-plugin-vue`'s `flat/essential` ruleset only (bug-prevention rules, e.g. missing `:key`, side effects in computed). Stylistic rulesets (`strongly-recommended`/`recommended`) are intentionally not used because the existing components don't follow a consistent formatting style (indentation, attribute wrapping) and enabling them would require a repo-wide reformat.

## OpenSpec

This repo uses OpenSpec for spec-driven change proposals (`openspec/config.yaml`, `.claude/commands/opsx/*`). Use `/opsx:propose "..."` to start a change, `/opsx:apply` to implement it, `/opsx:archive` to close it out.
