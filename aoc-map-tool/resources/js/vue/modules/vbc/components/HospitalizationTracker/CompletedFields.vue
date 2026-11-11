<template>
    <div class="completed-fields">
        <div class="completed-fields__field">
            <ui-select
                v-model="form.completedBy"
                :label="`${COMPLETED_BY_LABEL} *`"
                placeholder="Select..."
                :error="
                    errors.completedBy
                        ? `${COMPLETED_BY_LABEL} is required.`
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

        <ui-textarea v-model="form.notes" label="Completion notes" />

        <ui-button class="completed-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Completed outcome (AC25-AC29). Required-field validation is local to
// this component (design.md Decision 2). Defaults "Completed by" to the
// tracker's current assignee (AC26).
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
const COMPLETED_BY_LABEL = 'Completed by'

const form = reactive({
    completedBy: store.tracker?.assigned_to_id ?? '',
    notes: '',
})

// Per the ticket, only "Completed by" is required — notes stay optional.
const errors = reactive({
    completedBy: false,
})

function validate() {
    errors.completedBy = !form.completedBy
    return !errors.completedBy
}

function submit() {
    if (!validate()) return
    store.submitCompleted({ ...form })
}
</script>

<style scoped>
.completed-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
/* The column stretches the select edge-to-edge by default; capped to a more
   reasonable width, matching the Outcome bar's own selects above it. */
.completed-fields__field {
    max-width: 400px;
}
/* The column stretches every direct child to its full width by default; the
   Outcome bar's own Submit (Move to In Progress/Closed) sits in a row instead
   and stays compact, so this one is pinned back to its own width to match. */
.completed-fields__submit {
    align-self: flex-start;
}
</style>
