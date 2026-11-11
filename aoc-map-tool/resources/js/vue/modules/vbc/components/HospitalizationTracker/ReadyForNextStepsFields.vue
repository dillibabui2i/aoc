<template>
    <div class="next-steps-fields">
        <div class="next-steps-fields__field">
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

        <ui-button class="next-steps-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Ready for Next Steps outcome (AC17-AC22). Required-field validation is
// local to this component (design.md Decision 2). Defaults "Next Steps to
// be made by" to the internal "Who Sent to Review" field (AC18).
import { reactive } from 'vue'
import {
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

// Single source for the field's name, so the label's "*" suffix and the
// error message below can never drift apart from each other.
const NEXT_STEPS_BY_LABEL = 'Next Steps to be made by'

const form = reactive({
    nextStepsBy: store.tracker?.sent_to_review_by_id ?? '',
    notes: '',
})

// Per the ticket, only "Next Steps to be made by" is required — notes stay optional.
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
.next-steps-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
/* The column stretches the select edge-to-edge by default; capped to a more
   reasonable width, matching the Outcome bar's own selects above it. */
.next-steps-fields__field {
    max-width: 400px;
}
/* The column stretches every direct child to its full width by default; the
   Outcome bar's own Submit (Move to In Progress/Closed) sits in a row instead
   and stays compact, so this one is pinned back to its own width to match. */
.next-steps-fields__submit {
    align-self: flex-start;
}
</style>
