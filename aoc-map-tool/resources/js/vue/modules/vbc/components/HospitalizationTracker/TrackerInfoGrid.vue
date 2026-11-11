<template>
    <div class="info-grid">
        <div v-for="row in rowNumbers" :key="row" class="info-grid__row">
            <div
                v-for="field in rowsByNumber[row]"
                :key="field.key"
                class="info-grid__field"
                :class="{ 'info-grid__field--toggle': field.type === 'toggle' }"
                :style="fieldStyle(field)"
            >
                <ClientSearchAutocomplete
                    v-if="field.type === 'clientSearch'"
                />

                <template v-else-if="field.type === 'toggle'">
                    <label class="info-grid__toggle-label">{{
                        field.label
                    }}</label>
                    <ToggleSwitch
                        :model-value="
                            isEditableNow(field)
                                ? !!draft[field.key]
                                : !!displayValue(field)
                        "
                        :disabled="!isEditableNow(field)"
                        @update:model-value="
                            (val) => onEditableChange(field, val)
                        "
                    />
                </template>

                <ui-select
                    v-else-if="field.type === 'select' && isEditableNow(field)"
                    :label="field.required ? `${field.label} *` : field.label"
                    :model-value="asText(draft[field.key])"
                    :error="errorFor(field)"
                    @update:model-value="(val) => onEditableChange(field, val)"
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in optionsFor(field)"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>

                <ui-date-timepicker
                    v-else-if="
                        field.type === 'datetime' && isEditableNow(field)
                    "
                    :label="field.shortLabel || field.label"
                    :title="field.shortLabel ? field.label : undefined"
                    mode="datetime"
                    time-format="12"
                    :model-value="asText(draft[field.key])"
                    @update:model-value="(val) => onEditableChange(field, val)"
                />

                <ui-input
                    v-else
                    :label="field.label"
                    :model-value="asText(displayLabel(field))"
                    disabled
                />

                <!-- Outside the v-if chain above on purpose: it must not break
                     the chain, and it applies whatever input the field renders. -->
                <span v-if="savedFields[field.key]" class="info-grid__saved">
                    Updated
                </span>
                <!-- Selects show their failure through UiSelect's own error text;
                     the toggles have nowhere to put one, so they get it here. -->
                <span
                    v-else-if="field.type === 'toggle' && errorFor(field)"
                    class="info-grid__error"
                    >{{ errorFor(field) }}</span
                >
            </div>
        </div>

        <div v-if="canSubmit" class="info-grid__submit">
            <ui-button @click="submit">Submit</ui-button>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch, computed, onBeforeUnmount } from 'vue'
import {
    UiInput,
    UiSelect,
    UiButton,
    UiDateTimepicker,
} from '@angels-of-care/ui'
import ToggleSwitch from './ToggleSwitch.vue'
import ClientSearchAutocomplete from './ClientSearchAutocomplete.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { HEADER_FIELDS } from '@/modules/vbc/config/hospitalizationTrackerFields.js'

const store = useHospitalizationTracker()

const EDITABLE_KEYS = HEADER_FIELDS.filter((f) => f.editable).map((f) => f.key)
const TOGGLE_KEYS = HEADER_FIELDS.filter((f) => f.type === 'toggle').map(
    (f) => f.key,
)

const draft = reactive({})

// Required fields the user left empty. Kept apart from `store.formErrors` (which
// holds what the server said) so a local message clears the moment the field is
// answered, without waiting for a request.
const localErrors = reactive({})

const REQUIRED_FIELDS = HEADER_FIELDS.filter((f) => f.required)

// These fields save the moment they change, with no Save button to confirm it —
// so each one says so itself for a few seconds. Keyed by field, with the timer
// kept so a second edit restarts the countdown instead of stacking timeouts.
const SAVED_NOTICE_MS = 3000
const savedFields = reactive({})
const savedTimers = {}

function noticeSaved(key) {
    window.clearTimeout(savedTimers[key])
    savedFields[key] = true
    savedTimers[key] = window.setTimeout(() => {
        savedFields[key] = false
    }, SAVED_NOTICE_MS)
}

function clearSaved(key) {
    window.clearTimeout(savedTimers[key])
    savedFields[key] = false
}

onBeforeUnmount(() => {
    Object.values(savedTimers).forEach((timer) => window.clearTimeout(timer))
})

// Seeds the editable fields from whatever is authoritative right now: the saved
// tracker once it exists, the selected client before that. Seeding only from the
// client (as this used to) left the tracker-owned fields — assignee, level —
// blank whenever an existing record was opened, because a client profile has no
// such fields and the component mounts after the tracker is already in the store.
function syncDraft() {
    const source = store.tracker ?? store.selectedClient

    EDITABLE_KEYS.forEach((key) => {
        const fallback = TOGGLE_KEYS.includes(key) ? false : ''
        draft[key] = source?.[key] ?? fallback
        localErrors[key] = null
    })
}

// Fields whose options are answered by the backend rather than the config.
// Rows fill the modal width, each field taking its designed share of it.
function fieldStyle(field) {
    if (field.type === 'toggle') return null
    return { flex: `${field.grow ?? 1} 1 0` }
}

// The design system types `modelValue` as a String, but the API returns real
// numbers for ids, age and hours. They're handed over as text so Vue doesn't warn;
// `draft`/`displayValue` stay untyped, since the toggles need a real boolean
// (String(false) would be truthy).
function asText(value) {
    return value === null || value === undefined ? '' : String(value)
}

function optionsFor(field) {
    return field.optionsFrom ? (store[field.optionsFrom] ?? []) : field.options
}

// Runs on mount as well as on change, so opening an existing record shows its
// saved values. Re-seeding whenever the store replaces the tracker also keeps the
// grid honest when a field is changed elsewhere — e.g. the Ready for Review /
// Ready for Next Steps reassignment — instead of showing a stale local draft.
watch(() => [store.tracker, store.selectedClient], syncDraft, {
    immediate: true,
})

const rowNumbers = [1, 2, 3, 4, 5]
const rowsByNumber = computed(() => {
    const grouped = {}
    HEADER_FIELDS.forEach((field) => {
        grouped[field.row] = grouped[field.row] || []
        grouped[field.row].push(field)
    })
    return grouped
})

function displayValue(field) {
    const source =
        store.wizardStep === 'open' ? store.tracker : store.selectedClient
    return source?.[field.key] ?? ''
}

// A field that is no longer editable (Closed/Completed tracker) is rendered as a
// plain input, which would otherwise print what is stored rather than what the
// user picked — 'LEVEL_II_III' instead of 'Level II/III', an assignee's id
// instead of their name.
function displayLabel(field) {
    const value = displayValue(field)
    if (field.type !== 'select' || value === '') return value

    // `field.nameKey` is the resolved name the backend already sends
    // alongside the id (e.g. `assigned_to_name`) — used ahead of the options
    // lookup below, since that list is live/filtered (e.g. only assignees who
    // *currently* hold the ICM permission) and may no longer contain this id.
    if (field.nameKey) {
        const source =
            store.wizardStep === 'open' ? store.tracker : store.selectedClient
        const name = source?.[field.nameKey]
        if (name) return name
    }

    const option = optionsFor(field).find(
        (opt) => String(opt.value) === String(value),
    )

    return option?.label ?? value
}

function isEditableNow(field) {
    return (
        field.editable &&
        (store.wizardStep === 'confirmed' || store.wizardStep === 'open') &&
        store.tracker?.status !== 'Closed' &&
        store.tracker?.status !== 'Completed'
    )
}

function errorFor(field) {
    return localErrors[field.key] ?? store.formErrors[field.key] ?? null
}

function isBlank(value) {
    return value === null || value === undefined || value === ''
}

// Marks every empty required field and answers whether the form may be sent.
function validate() {
    REQUIRED_FIELDS.forEach((field) => {
        localErrors[field.key] = isBlank(draft[field.key])
            ? `${field.label} is required.`
            : null
    })

    return REQUIRED_FIELDS.every((field) => !localErrors[field.key])
}

async function onEditableChange(field, value) {
    draft[field.key] = value
    clearSaved(field.key)

    if (field.required && isBlank(value)) {
        // A required field can't be cleared: saying so here beats letting the
        // request go out and come back 422 with the value already gone from view.
        localErrors[field.key] = `${field.label} is required.`

        return
    }

    localErrors[field.key] = null
    store.clearFormError(field.key)

    // Once the tracker exists (Open state), edits persist immediately;
    // before that (Confirmed state, pre-Submit), they're staged in `draft`
    // and sent as part of the create payload on Submit.
    if (store.wizardStep !== 'open') return

    if (await store.autoSaveField(field.key, value)) {
        noticeSaved(field.key)
    }
}

const canSubmit = computed(
    () => store.wizardStep === 'confirmed' && !!store.selectedClient,
)

function submit() {
    if (!validate()) return
    store.submitTracker({ ...draft })
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

/* Fills narrow screens, but stops at the width the design was drawn at —
   past that the fields just stretch and the form reads worse. */
.info-grid {
    width: 100%;
    max-width: 1180px;
}
.info-grid__row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dee2e6;
}
.info-grid__row:last-of-type {
    border-bottom: none;
}
.info-grid__field {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 4px;
    min-width: 90px;
}
/* Toggles sit on one line — label, then the switch — and don't stretch. */
.info-grid__field--toggle {
    flex: 0 0 auto;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    margin-right: 40px;
}
.info-grid__toggle-label {
    font-size: 15px;
    color: #212529;
    white-space: nowrap;
}
/* Same size and position as UiSelect's own error text, so a confirmation and a
   failure read as the same kind of message about the field — just a different
   colour. The toggles have no wrapper of their own, so theirs sits inline after
   the switch. */
.info-grid__saved,
.info-grid__error {
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
}
.info-grid__saved {
    color: $success;
}
.info-grid__error {
    color: $danger;
}
.info-grid__submit {
    margin-top: 8px;
    text-align: right;
}
</style>
