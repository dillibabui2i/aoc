<template>
    <div v-if="showBar" class="ed-outcome-bar">
        <div class="ed-outcome-bar__controls">
            <ui-select
                v-model="outcome"
                label="Outcome"
                placeholder="Select an outcome"
            >
                <option v-for="opt in outcomeOptions" :key="opt" :value="opt">
                    {{ opt }}
                </option>
            </ui-select>
        </div>

        <!-- Covers anything submitting the chosen Fields component below can fail with (illegal
             transition, a 409/422 from the server) that has no field of its own to attach to — each
             Fields component's own required-field errors render inline instead. -->
        <p v-if="store.outcomeError" class="ed-outcome-bar__error">
            {{ store.outcomeError }}
        </p>

        <EdReadyForReviewFields v-if="outcome === 'Ready for Review'" />
        <EdReadyForNextStepsFields
            v-else-if="outcome === 'Ready for Next Steps'"
        />
        <EdCompletedFields v-else-if="outcome === 'Complete'" />
        <EdCloseFields v-else-if="outcome === 'Close'" />
    </div>
</template>

<script setup>
// ED Visit Outcome workflow (PD-4069): In Progress -> Ready for Review -> Ready for Next Steps ->
// Complete, with Close reachable from any of the first three (AC1/AC4/AC8/AC11) — a narrower, different
// transition set than Hospitalization's own OutcomeSubmitBar.vue, so this is ED's own bar rather than a
// parametrization of that one, per the "separate module, reuse only presentational pieces" pattern
// established for PD-3983.
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { UiSelect } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import EdReadyForReviewFields from './EdReadyForReviewFields.vue'
import EdReadyForNextStepsFields from './EdReadyForNextStepsFields.vue'
import EdCompletedFields from './EdCompletedFields.vue'
import EdCloseFields from './EdCloseFields.vue'

const store = useEdVisitTracker()

const outcome = ref('')

// Every outcome here submits through its own Fields component, which never calls back into this bar —
// so the tracker's status changing (the one signal every one of them shares) is what clears the picked
// dropdown value once a transition actually lands, same reasoning as Hospitalization's own bar.
watch(
    () => store.tracker?.status,
    () => {
        outcome.value = ''
    },
)

// A picked outcome is a transition that has not been submitted yet.
watch(
    () => outcome.value !== '',
    (picked) => store.setUnsaved('outcome', picked),
)
onBeforeUnmount(() => store.setUnsaved('outcome', false))

// AC10/AC13: hidden entirely once the tracker is Complete or Closed.
const showBar = computed(
    () =>
        store.tracker?.status !== 'Completed' &&
        store.tracker?.status !== 'Closed',
)

const outcomeOptions = computed(() => {
    switch (store.tracker?.status) {
        case 'In Progress':
            return ['Ready for Review', 'Close']
        case 'Ready for Review':
            return ['Ready for Next Steps', 'Close']
        case 'Ready for Next Steps':
            return ['Complete', 'Close']
        default:
            return []
    }
})
</script>

<style scoped>
.ed-outcome-bar {
    margin: 12px 0;
}
.ed-outcome-bar__controls {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    max-width: 260px;
}
.ed-outcome-bar__error {
    margin: 4px 0 0;
    font-size: 12px;
    color: #dc3545;
}
</style>
