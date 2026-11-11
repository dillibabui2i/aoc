<template>
    <div class="discharge-fields">
        <div class="discharge-fields__row">
            <div class="discharge-fields__field">
                <ui-date-timepicker
                    v-model="form.expectedDischargeDate"
                    label="Expected Discharge Date"
                    mode="date"
                />
            </div>
            <div class="discharge-fields__field">
                <ui-date-timepicker
                    v-model="form.dischargeDate"
                    :label="`${DISCHARGE_DATE_LABEL} *`"
                    mode="date"
                    :error="
                        errors.dischargeDate
                            ? `${DISCHARGE_DATE_LABEL} is required.`
                            : null
                    "
                />
            </div>
        </div>

        <div class="discharge-fields__row discharge-fields__row--toggle">
            <ToggleSwitch v-model="form.firstBillableShiftCompleted" />
            <label
                >First billable shift completed within 48 hours following
                hospital discharge?</label
            >
            <div v-if="form.firstBillableShiftCompleted" class="discharge-fields__field">
                <ui-date-timepicker
                    v-model="form.firstBillableShiftDate"
                    :label="`${FIRST_BILLABLE_SHIFT_DATE_LABEL} *`"
                    mode="date"
                    :error="
                        errors.firstBillableShiftDate
                            ? `${FIRST_BILLABLE_SHIFT_DATE_LABEL} is required.`
                            : null
                    "
                />
            </div>
        </div>

        <ui-textarea v-model="form.dischargeNotes" label="Discharge Notes" />

        <ui-button class="discharge-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Discharge outcome (AC3-AC6). Required-field validation is local to this
// component (design.md Decision 2) — errors are only set on submit, not
// eagerly, so `UiDateTimepicker`'s own `required` prop (which flags an empty
// field as invalid immediately) isn't used here; `:error` is passed instead.
import { reactive } from 'vue'
import {
    UiDateTimepicker,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import ToggleSwitch from './ToggleSwitch.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

// Single source for each field's name, so the label's "*" suffix and the
// error message below it can never drift apart from each other.
const DISCHARGE_DATE_LABEL = 'Discharge Date'
const FIRST_BILLABLE_SHIFT_DATE_LABEL = 'First billable shift date'

const form = reactive({
    expectedDischargeDate: '',
    dischargeDate: '',
    firstBillableShiftCompleted: false,
    firstBillableShiftDate: '',
    dischargeNotes: '',
})

// Per the ticket, Discharge Date is the only unconditionally required field —
// Expected Discharge Date and Discharge Notes stay optional.
const errors = reactive({
    dischargeDate: false,
    firstBillableShiftDate: false,
})

function validate() {
    errors.dischargeDate = !form.dischargeDate
    errors.firstBillableShiftDate =
        form.firstBillableShiftCompleted && !form.firstBillableShiftDate
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    store.submitDischarge({ ...form })
}
</script>

<style scoped>
.discharge-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.discharge-fields__row {
    display: flex;
    align-items: flex-end;
    gap: 12px;
}
.discharge-fields__row--toggle {
    align-items: center;
}
.discharge-fields__field {
    display: flex;
    flex-direction: column;
    gap: 4px;
    flex: 1;
}
/* The column stretches every direct child to its full width by default;
   the Outcome bar's own Submit (Move to In Progress/Closed) sits in a row
   instead and stays compact, so this one is pinned back to its own width
   to match. */
.discharge-fields__submit {
    align-self: flex-start;
}
</style>
