<template>
    <div class="ed-follow-up-tab">
        <ui-select
            v-model="outcome"
            label="Follow-up Outcome"
            placeholder="Select an outcome"
        >
            <option
                v-for="opt in outcomeOptions"
                :key="opt.value"
                :value="opt.value"
            >
                {{ opt.label }}
            </option>
        </ui-select>

        <template v-if="outcome">
            <ui-textarea
                v-model="notes"
                :label="`${NOTES_LABEL} *`"
                :error="notesError ? `${NOTES_LABEL} is required.` : null"
            />
        </template>

        <p v-if="store.followUpError" class="ed-follow-up-tab__error">
            {{ store.followUpError }}
        </p>

        <ui-button class="ed-follow-up-tab__submit" @click="submit">
            Submit
        </ui-button>
    </div>
</template>

<script setup>
// ED Visit Follow-up tab (PD-4073): Not Started -> First -> Second -> Third Attempt, with Completed
// available at every step before the last. Required-field validation is local, like the Outcome
// bar's Fields components; the backend enforces the same transitions.
import { ref, computed, watch } from 'vue'
import {
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import {
    useEdVisitTracker,
    ED_FOLLOW_UP_OPTIONS,
} from '@/modules/vbc/store/edVisitTracker.store.js'

const store = useEdVisitTracker()

const NOTES_LABEL = 'Follow-up Notes'

const outcome = ref('')
const notes = ref('')
const notesError = ref(false)

const outcomeOptions = computed(
    () => ED_FOLLOW_UP_OPTIONS[store.tracker?.ed_follow_up_status] ?? [],
)

// A submit that lands changes the follow-up status — reset the form for the next step.
watch(
    () => store.tracker?.ed_follow_up_status,
    () => {
        outcome.value = ''
        notes.value = ''
        notesError.value = false
    },
)

async function submit() {
    if (!outcome.value) return
    notesError.value = !notes.value?.trim()
    if (notesError.value) return

    await store.submitFollowUp({ outcome: outcome.value, notes: notes.value })
}
</script>

<style scoped>
.ed-follow-up-tab {
    display: flex;
    flex-direction: column;
    gap: 12px;
    max-width: 480px;
}
.ed-follow-up-tab__submit {
    align-self: flex-start;
}
.ed-follow-up-tab__error {
    margin: 0;
    font-size: 12px;
    color: #dc3545;
}
</style>
