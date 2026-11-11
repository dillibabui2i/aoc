<template>
    <div class="ed-info-grid">
        <div v-for="row in rowNumbers" :key="row" class="ed-info-grid__row">
            <div
                v-for="field in rowsByNumber[row]"
                :key="field.key"
                class="ed-info-grid__field"
                :class="{
                    'ed-info-grid__field--toggle': field.type === 'toggle',
                }"
                :style="fieldStyle(field)"
            >
                <EdClientSearchAutocomplete v-if="field.type === 'clientSearch'" />

                <template v-else-if="field.type === 'toggle'">
                    <label class="ed-info-grid__toggle-label">{{
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
                        v-if="fallbackOptionFor(field)"
                        :value="fallbackOptionFor(field).value"
                    >
                        {{ fallbackOptionFor(field).label }}
                    </option>
                    <option
                        v-for="opt in optionsFor(field)"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>

                <ui-input
                    v-else
                    :label="field.label"
                    :model-value="asText(displayLabel(field))"
                    disabled
                />

                <span v-if="savedFields[field.key]" class="ed-info-grid__saved">
                    Updated
                </span>
                <span
                    v-else-if="field.type === 'toggle' && errorFor(field)"
                    class="ed-info-grid__error"
                    >{{ errorFor(field) }}</span
                >
            </div>
        </div>

        <div v-if="canSubmit" class="ed-info-grid__submit">
            <ui-button @click="submit">Save</ui-button>
        </div>
    </div>
</template>

<script setup>
import { reactive, watch, computed, onBeforeUnmount } from 'vue'
import { UiInput, UiSelect, UiButton } from '@angels-of-care/ui'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'
import EdClientSearchAutocomplete from './EdClientSearchAutocomplete.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { HEADER_FIELDS } from '@/modules/vbc/config/edVisitTrackerFields.js'

const store = useEdVisitTracker()

const EDITABLE_KEYS = HEADER_FIELDS.filter((f) => f.editable).map((f) => f.key)
const TOGGLE_KEYS = HEADER_FIELDS.filter((f) => f.type === 'toggle').map(
    (f) => f.key,
)

const draft = reactive({})
const localErrors = reactive({})
const REQUIRED_FIELDS = HEADER_FIELDS.filter((f) => f.required)

// AC19: editable fields auto-save, each confirming itself for a few seconds —
// there is no Save button on this path to confirm it otherwise.
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

function syncDraft() {
    const source = store.tracker ?? store.selectedClient

    EDITABLE_KEYS.forEach((key) => {
        const fallback = TOGGLE_KEYS.includes(key) ? false : ''
        draft[key] = source?.[key] ?? fallback
        localErrors[key] = null
    })
}

function fieldStyle(field) {
    if (field.type === 'toggle') return null
    return { flex: `${field.grow ?? 1} 1 0` }
}

function asText(value) {
    return value === null || value === undefined ? '' : String(value)
}

function optionsFor(field) {
    return field.optionsFrom ? (store[field.optionsFrom] ?? []) : field.options
}

// PD-5636: the saved assignee may be missing from the live options (list still loading, or they no
// longer qualify) — keep them selectable under their saved name instead of rendering an empty select.
function fallbackOptionFor(field) {
    const savedId = draft[field.key]
    const name = field.nameKey ? store.tracker?.[field.nameKey] : null
    if (!name || isBlank(savedId)) return null

    const listed = optionsFor(field).some(
        (opt) => String(opt.value) === String(savedId),
    )

    return listed ? null : { value: savedId, label: name }
}

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

function displayLabel(field) {
    const value = displayValue(field)
    if (field.type !== 'select' || value === '') return value

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

// AC2: all fields disabled except Client Name until a client is confirmed; a Closed/Completed tracker
// has no equivalent in this story (ED trackers only reach "In Progress" here), but the guard mirrors
// the Hospitalization form's in case a later story reaches it through this same field set.
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
        localErrors[field.key] = `${field.label} is required.`

        return
    }

    localErrors[field.key] = null
    store.clearFormError(field.key)

    // Before the tracker exists (Confirmed state), edits are staged and sent with Save (AC6); once it
    // exists (Open state), they persist immediately (AC19).
    if (store.wizardStep !== 'open') return

    if (await store.autoSaveField(field.key, value)) {
        noticeSaved(field.key)
    }
}

// AC4/AC6: a valid client must be selected before the record can be saved.
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

.ed-info-grid {
    width: 100%;
    max-width: 1180px;
}
.ed-info-grid__row {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-end;
    gap: 10px 12px;
    margin-bottom: 5px;
    padding-bottom: 5px;
    border-bottom: 1px solid #dee2e6;
}
.ed-info-grid__row:last-of-type {
    border-bottom: none;
}
.ed-info-grid__field {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 4px;
    min-width: 90px;
}
.ed-info-grid__field--toggle {
    flex: 0 0 auto;
    flex-direction: row;
    align-items: center;
    gap: 10px;
    margin-right: 40px;
}
.ed-info-grid__toggle-label {
    font-size: 15px;
    color: #212529;
    white-space: nowrap;
}
.ed-info-grid__saved,
.ed-info-grid__error {
    font-size: 12px;
    line-height: 1.2;
    white-space: nowrap;
}
.ed-info-grid__saved {
    color: $success;
}
.ed-info-grid__error {
    color: $danger;
}
.ed-info-grid__submit {
    margin-top: 8px;
    text-align: right;
}
</style>
