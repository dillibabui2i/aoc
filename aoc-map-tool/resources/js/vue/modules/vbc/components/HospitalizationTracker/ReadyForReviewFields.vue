<template>
    <div class="review-fields">
        <div class="review-fields__field">
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

        <ui-button class="review-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Ready for Review outcome (AC9-AC14). Required-field validation is local
// to this component (design.md Decision 2).
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
const REVIEWER_LABEL = 'Review to be made by'

const form = reactive({
    reviewer: '',
    notes: '',
})

// Per the ticket, only "Review to be made by" is required — notes stay optional.
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
.review-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
/* The column stretches the select edge-to-edge by default; capped to a more
   reasonable width, matching the Outcome bar's own selects above it. */
.review-fields__field {
    max-width: 400px;
}
/* The column stretches every direct child to its full width by default; the
   Outcome bar's own Submit (Move to In Progress/Closed) sits in a row instead
   and stays compact, so this one is pinned back to its own width to match. */
.review-fields__submit {
    align-self: flex-start;
}
</style>
