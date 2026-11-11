<template>
    <div class="ed-review-fields">
        <div class="ed-review-fields__field">
            <ui-select
                v-model="form.reviewer"
                :label="`${REVIEWER_LABEL} *`"
                placeholder="Select..."
                :error="
                    errors.reviewer ? `${REVIEWER_LABEL} is required.` : null
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

        <ui-textarea v-model="form.notes" label="Ready for Review notes" />

        <ui-button class="ed-review-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Ready for Review outcome (PD-4069 AC2/AC3). Required-field validation is local to this component,
// mirroring HospitalizationTracker/ReadyForReviewFields.vue — "Review to be made by" is the only
// required field (task.md), notes stay optional.
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
const REVIEWER_LABEL = 'Review to be made by'

const form = reactive({
    reviewer: '',
    notes: '',
})

const errors = reactive({
    reviewer: false,
})

function validate() {
    errors.reviewer = !form.reviewer
    return !errors.reviewer
}

function submit() {
    if (!validate()) return
    store.submitReadyForReview({ ...form })
}
</script>

<style scoped>
.ed-review-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
/* The column stretches the select edge-to-edge by default; capped to a more reasonable width, matching
   the Outcome bar's own select above it. */
.ed-review-fields__field {
    max-width: 400px;
}
.ed-review-fields__submit {
    align-self: flex-start;
}
</style>
