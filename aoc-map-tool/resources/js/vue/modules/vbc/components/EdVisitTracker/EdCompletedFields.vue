<template>
    <div class="ed-completed-fields">
        <div class="ed-completed-fields__field">
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

        <ui-button class="ed-completed-fields__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// Complete outcome (PD-4069 AC9/AC10). Required-field validation is local to this component, mirroring
// HospitalizationTracker/CompletedFields.vue — defaults "Completed by" to the tracker's current
// assignee (stakeholder-approved assumption, task.md's AC9 doesn't specify one explicitly).
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
const COMPLETED_BY_LABEL = 'Completed by'

const form = reactive({
    completedBy: store.tracker?.assigned_to_id ?? '',
    notes: '',
})

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
.ed-completed-fields {
    margin: 12px 0;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.ed-completed-fields__field {
    max-width: 400px;
}
.ed-completed-fields__submit {
    align-self: flex-start;
}
</style>
