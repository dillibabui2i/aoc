<template>
    <div class="current-info-tab">
        <div
            v-for="row in rowKeys"
            :key="row"
            class="row current-info-tab__row"
        >
            <div
                v-for="field in rowsByKey[row]"
                :key="field.key"
                class="col current-info-tab__field"
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

        <!-- One button for the whole section: these fields are validated
             against each other's required rules, which can only happen once the
             user says they are done — so nothing here saves on its own. -->
        <div v-if="!isDisabled" class="current-info-tab__submit">
            <span v-if="savedNotice" class="current-info-tab__saved"
                >Updated</span
            >
            <ui-button :disabled="saving" @click="submit">{{
                saving ? 'Saving...' : 'Submit'
            }}</ui-button>
        </div>
    </div>
</template>

<script setup>
import { computed, reactive, ref, watch, onBeforeUnmount } from 'vue'
import { UiButton } from '@angels-of-care/ui'
import CurrentInfoField from './CurrentInfoField.vue'
import { CURRENT_INFO_FIELDS } from '@/modules/vbc/config/hospitalizationTrackerFields.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

// Discharge-and-later statuses — matches every status the discharge
// lifecycle can reach once Discharge has happened.
const POST_DISCHARGE_STATUSES = [
    'Discharged',
    'Ready for Review',
    'Ready for Next Steps',
    'Completed',
    'Closed',
]

const SAVED_NOTICE_MS = 3000

const draft = reactive({})
const errors = reactive({})
const saving = ref(false)
const savedNotice = ref(false)
let savedTimer = null

// Every field of this tab is disabled once the tracker is Closed (AC18) or
// Completed (AC32).
const isDisabled = computed(
    () =>
        store.tracker?.status === 'Closed' ||
        store.tracker?.status === 'Completed',
)

const visibleFields = computed(() =>
    CURRENT_INFO_FIELDS.filter((field) => {
        if (
            field.postDischargeOnly &&
            !POST_DISCHARGE_STATUSES.includes(store.tracker?.status)
        ) {
            return false
        }
        // `draft`, not `store.tracker` — a field's visibility must react to
        // what the user is currently editing (e.g. toggling the "completed"
        // switch), not to what was last saved on the server.
        if (field.visibleIf && !field.visibleIf(draft)) return false
        return true
    }),
)

const rowKeys = computed(() => [
    ...new Set(visibleFields.value.map((field) => field.row)),
])
const rowsByKey = computed(() => {
    const grouped = {}
    visibleFields.value.forEach((field) => {
        grouped[field.row] = grouped[field.row] || []
        grouped[field.row].push(field)
    })
    return grouped
})

// Re-seeded whenever the store replaces the tracker, so a save elsewhere (or a
// refresh after a follow-up) is reflected instead of showing a stale draft.
function blankValue(field) {
    if (field.type === 'multiselect') return []

    return field.type === 'toggle' ? false : ''
}

function syncDraft() {
    CURRENT_INFO_FIELDS.forEach((field) => {
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
    CURRENT_INFO_FIELDS.some(
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

function isBlank(value) {
    if (Array.isArray(value)) return !value.length

    return value === null || value === undefined || value === ''
}

// A toggle is never blank — false is an answer, not a missing one — so only the
// value-bearing fields can fail this.
function validate() {
    let valid = true

    visibleFields.value.forEach((field) => {
        const missing =
            !field.optional &&
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
        visibleFields.value.forEach((field) => {
            values[field.key] = draft[field.key]
        })

        if (await store.submitCurrentInfo(values)) {
            noticeSaved()
        }
    } finally {
        saving.value = false
    }
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.current-info-tab__row {
    margin-bottom: 12px;
}
.current-info-tab__field {
    min-width: 200px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}
.current-info-tab__field > * {
    width: 100%;
}
.current-info-tab__submit {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 8px;
}
.current-info-tab__saved {
    font-size: 12px;
    color: $success;
}
</style>
