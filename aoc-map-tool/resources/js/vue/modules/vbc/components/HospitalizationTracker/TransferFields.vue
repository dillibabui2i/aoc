<template>
    <div class="transfer-fields">
        <div
            v-for="row in rowKeys"
            :key="row"
            class="row transfer-fields__row"
        >
            <div
                v-for="field in rowsByKey[row]"
                :key="field.key"
                class="col transfer-fields__field"
            >
                <CurrentInfoField
                    :field="field"
                    :model-value="form[field.key]"
                    :error="errors[field.key]"
                    @update:model-value="(val) => onChange(field, val)"
                />
            </div>
        </div>

        <ui-button class="transfer-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Transfer (Closed outcome, "Transfer" reason): the Current Hospitalization Info of the hospitalization
// the patient is moved to. Same fields and required rules as the Current Hospitalization Info tab
// (everything not marked `optional`) — except Declined Program Contact, which a transfer also requires,
// and Admission Reason, which is replaced by Transfer Reason (HospitalizationTrackerFieldEnum::
// transferFields()/requiredForTransferFields()). Validated locally on submit.
import { computed, reactive } from 'vue'
import { UiButton } from '@angels-of-care/ui'
import CurrentInfoField from './CurrentInfoField.vue'
import { CURRENT_INFO_FIELDS } from '@/modules/vbc/config/hospitalizationTrackerFields.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

// Optional on the Current Hospitalization Info tab, required for a transfer.
const REQUIRED_FOR_TRANSFER = ['declined_program_contact']

// Transfer Reason takes Admission Reason's place (same textarea, same row); it is not a tracker field,
// so it is sent separately as `notes` on submit.
const TRANSFER_REASON_KEY = 'transfer_reason'

// Discharge-time fields only exist once the new hospitalization is discharged.
const FIELDS = CURRENT_INFO_FIELDS.filter(
    (field) => !field.postDischargeOnly,
).map((field) => {
    if (field.key === 'admission_reason') {
        return { ...field, key: TRANSFER_REASON_KEY, label: 'Transfer Reason' }
    }

    return REQUIRED_FOR_TRANSFER.includes(field.key)
        ? { ...field, optional: false }
        : field
})

function blankValue(field) {
    if (field.type === 'multiselect') return []

    return field.type === 'toggle' ? false : ''
}

const form = reactive(
    Object.fromEntries(FIELDS.map((field) => [field.key, blankValue(field)])),
)
const errors = reactive({})

const rowKeys = computed(() => [...new Set(FIELDS.map((field) => field.row))])
const rowsByKey = computed(() => {
    const grouped = {}
    FIELDS.forEach((field) => {
        grouped[field.row] = grouped[field.row] || []
        grouped[field.row].push(field)
    })
    return grouped
})

function isBlank(value) {
    if (Array.isArray(value)) return !value.length

    return value === null || value === undefined || value === ''
}

// A toggle is never blank — false is an answer.
function validate() {
    let valid = true

    FIELDS.forEach((field) => {
        const missing =
            !field.optional && field.type !== 'toggle' && isBlank(form[field.key])
        errors[field.key] = missing
            ? `${field.shortLabel || field.label} is required.`
            : null
        if (missing) valid = false
    })

    return valid
}

function onChange(field, value) {
    form[field.key] = value
    errors[field.key] = null
}

function submit() {
    if (!validate()) return
    const { [TRANSFER_REASON_KEY]: notes, ...fields } = form
    store.submitTransfer({ fields, notes })
}
</script>

<style scoped>
.transfer-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.transfer-fields__field {
    min-width: 200px;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
}
.transfer-fields__field > * {
    width: 100%;
}
/* Pinned back to its own width, like DischargeFields' Submit. */
.transfer-fields__submit {
    align-self: flex-start;
}
</style>
