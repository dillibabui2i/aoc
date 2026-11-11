<template>
    <div class="ed-next-steps-fields">
        <div class="ed-next-steps-fields__field">
            <ui-select
                v-model="form.nextStepsBy"
                :label="`${NEXT_STEPS_BY_LABEL} *`"
                placeholder="Select..."
                :error="
                    errors.nextStepsBy
                        ? `${NEXT_STEPS_BY_LABEL} is required.`
                        : null
                "
            >
                <option value="">Select...</option>
                <option
                    v-for="opt in store.assignees"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </ui-select>
        </div>

        <ui-textarea v-model="form.notes" label="Ready for Next Steps notes" />

        <ui-button class="ed-next-steps-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Ready for Next Steps outcome (PD-4069 AC5-AC7). Required-field validation is local to this component,
// mirroring HospitalizationTracker/ReadyForNextStepsFields.vue — defaults "Next Steps to be made by" to
// who the tracker was previously sent to review by (AC6).
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
const NEXT_STEPS_BY_LABEL = 'Next steps to be made by'

const form = reactive({
    nextStepsBy: store.tracker?.sent_to_review_by_id ?? '',
    notes: '',
})

const errors = reactive({
    nextStepsBy: false,
})

function validate() {
    errors.nextStepsBy = !form.nextStepsBy
    return !errors.nextStepsBy
}

function submit() {
    if (!validate()) return
    store.submitReadyForNextSteps({ ...form })
}
</script>

<style scoped>
.ed-next-steps-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ed-next-steps-fields__field {
    max-width: 400px;
}
.ed-next-steps-fields__submit {
    align-self: flex-start;
}
</style>
