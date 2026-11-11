<template>
    <div class="ed-close-fields">
        <div class="ed-close-fields__field">
            <ui-select
                v-model="form.reason"
                :label="`${REASON_LABEL} *`"
                placeholder="Select..."
                :error="errors.reason ? `${REASON_LABEL} is required.` : null"
            >
                <option value="">Select...</option>
                <option value="OPENED_BY_MISTAKE">Opened by Mistake</option>
                <option value="HOSPITALIZATION">Hospitalization</option>
            </ui-select>
        </div>

        <ui-textarea v-model="form.notes" label="Closure notes" />

        <ui-button class="ed-close-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Close outcome (PD-4069 AC11-AC13). Unlike Hospitalization's Close (reason + Submit only, inline in
// OutcomeSubmitBar.vue), ED's Close also collects a free-text "Closure notes" field, so it gets its own
// Fields component rather than reusing that inline shape. The dropdown's option values are already the
// backend's own closing-reason enum values (UpdateHospitalizationTrackerOutcomeRequest's ED-specific
// accepted set) — "ED Visit" (Hospitalization's own reason) is deliberately not offered here (AC12
// lists only these two).
import { reactive } from 'vue'
import {
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const store = useEdVisitTracker()

// Single source for the field's name, so the label's "*" suffix and the error message below can never
// drift apart from each other.
const REASON_LABEL = 'Reason'

const form = reactive({
    reason: '',
    notes: '',
})

const errors = reactive({
    reason: false,
})

function validate() {
    errors.reason = !form.reason
    return !errors.reason
}

function submit() {
    if (!validate()) return
    store.submitClosed({ ...form })
}
</script>

<style scoped>
.ed-close-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ed-close-fields__field {
    max-width: 400px;
}
.ed-close-fields__submit {
    align-self: flex-start;
}
</style>
