<template>
    <div class="ed-info-tab">
        <div v-for="row in rowKeys" :key="row" class="row ed-info-tab__row">
            <div
                v-for="field in visibleFields(rowsByKey[row])"
                :key="field.key"
                class="col ed-info-tab__field"
            >
                <CurrentInfoField
                    :field="field"
                    :model-value="draft[field.key]"
                    :error="errors[field.key] ?? store.formErrors[field.key]"
                    :disabled="isDisabled"
                    @update:model-value="(val) => onChange(field, val)"
                />
            </div>
        </div>

        <!-- One button for the whole section (AC10): these fields are validated
             against each other's required rules, which can only happen once the
             user says they are done — same reasoning as the Hospitalization
             Record form's Current Hospitalization Info tab. Hidden once the
             tracker is read-only (PD-4069 AC10/AC13: Complete/Closed). -->
        <div v-if="!isDisabled" class="ed-info-tab__submit">
            <span v-if="savedNotice" class="ed-info-tab__saved">Updated</span>
            <ui-button :disabled="saving" @click="submit">{{
                saving ? 'Saving...' : 'Save'
            }}</ui-button>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch, onBeforeUnmount } from 'vue'
import { UiButton } from '@angels-of-care/ui'
import CurrentInfoField from '@/modules/vbc/components/HospitalizationTracker/CurrentInfoField.vue'
import { HOSPITALIZATION_INFO_FIELDS } from '@/modules/vbc/config/edVisitTrackerFields.js'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const store = useEdVisitTracker()

const SAVED_NOTICE_MS = 3000

const draft = reactive({})
const errors = reactive({})
const saving = ref(false)
const savedNotice = ref(false)
let savedTimer = null

// PD-4069 AC10/AC13: every field of this tab is read-only once the tracker is Complete or Closed,
// mirroring HospitalizationTracker/CurrentHospitalizationInfoTab.vue's own `isDisabled`.
const isDisabled = computed(
    () =>
        store.tracker?.status === 'Completed' ||
        store.tracker?.status === 'Closed',
)

function blankValue(field) {
    if (field.type === 'multiselect') return []

    return field.type === 'toggle' ? false : ''
}

// Vaccination dates are only shown once their own vaccine question is answered "Yes" (matches the
// backend's required_if rule) — hidden fields are skipped from both rendering and validation.
function visibleFields(fields) {
    return fields.filter(
        (field) => !field.visibleWhen || field.visibleWhen(draft),
    )
}

function syncDraft() {
    HOSPITALIZATION_INFO_FIELDS.forEach((field) => {
        draft[field.key] = store.tracker?.[field.key] ?? blankValue(field)
        errors[field.key] = null
    })
}

watch(() => store.tracker, syncDraft, { immediate: true })

// Anything in the draft that differs from what the server has is work the Submit button has not
// committed yet; reported to the store so leaving the tracker can warn about it.
function sameValue(a, b) {
    if (Array.isArray(a) || Array.isArray(b)) {
        return JSON.stringify(a ?? []) === JSON.stringify(b ?? [])
    }

    return String(a ?? '') === String(b ?? '')
}

const hasUnsubmittedChanges = computed(() =>
    HOSPITALIZATION_INFO_FIELDS.some(
        (field) =>
            !sameValue(
                draft[field.key],
                store.tracker?.[field.key] ?? blankValue(field),
            ),
    ),
)

watch(
    hasUnsubmittedChanges,
    (dirty) => store.setUnsaved('currentInfo', dirty),
    { immediate: true },
)

// PD-5800: highlight the fields a blocked status change reported as empty, by name, like validate() does.
watch(
    () => store.outcomeMissingFields,
    (keys) => {
        HOSPITALIZATION_INFO_FIELDS.filter((field) =>
            keys.includes(field.key),
        ).forEach((field) => {
            errors[field.key] = `${field.shortLabel || field.label} is required.`
        })
    },
)

const rowKeys = computed(() => [
    ...new Set(HOSPITALIZATION_INFO_FIELDS.map((field) => field.row)),
])
const rowsByKey = computed(() => {
    const grouped = {}
    HOSPITALIZATION_INFO_FIELDS.forEach((field) => {
        grouped[field.row] = grouped[field.row] || []
        grouped[field.row].push(field)
    })
    return grouped
})

function isBlank(value) {
    if (Array.isArray(value)) return !value.length

    return value === null || value === undefined || value === ''
}

function validate() {
    let valid = true

    HOSPITALIZATION_INFO_FIELDS.forEach((field) => {
        // A vaccination date is only required while its own vaccine question is visibly answered
        // "Yes" — matches the backend's required_if rule (UpdateEdVisitInfoRequest). A hidden one is
        // never missing, regardless of its `optional` flag.
        const missing = field.visibleWhen
            ? field.visibleWhen(draft) && isBlank(draft[field.key])
            : !field.optional &&
              field.type !== 'toggle' &&
              isBlank(draft[field.key])
        errors[field.key] = missing
            ? `${field.shortLabel || field.label} is required.`
            : null
        if (missing) valid = false
    })

    return valid
}

function onChange(field, value) {
    draft[field.key] = value
    errors[field.key] = null
    savedNotice.value = false
}

function noticeSaved() {
    window.clearTimeout(savedTimer)
    savedNotice.value = true
    savedTimer = window.setTimeout(() => {
        savedNotice.value = false
    }, SAVED_NOTICE_MS)
}

onBeforeUnmount(() => {
    window.clearTimeout(savedTimer)
    store.setUnsaved('currentInfo', false)
})

async function submit() {
    if (!validate()) return

    saving.value = true
    try {
        const values = {}
        HOSPITALIZATION_INFO_FIELDS.forEach((field) => {
            values[field.key] = draft[field.key]
        })

        if (await store.submitInfo(values)) {
            noticeSaved()
        }
    } finally {
        saving.value = false
    }
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.ed-info-tab__row {
    margin-bottom: 12px;
}
.ed-info-tab__field {
    min-width: 200px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}
.ed-info-tab__field > * {
    width: 100%;
}
.ed-info-tab__submit {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 8px;
}
.ed-info-tab__saved {
    font-size: 12px;
    color: $success;
}
</style>
