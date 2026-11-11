<template>
    <div v-if="showBar" class="outcome-bar">
        <ui-alert
            v-if="showAdmissionBanner"
            variant="info"
            message="This hospitalization tracker has been open for more than 24 hours since its opening. Please review all the data and select the proper outcome below:"
            class="outcome-bar__banner"
        />

        <div class="outcome-bar__controls">
            <ui-select
                v-model="outcome"
                label="Outcome"
                placeholder="Select an outcome"
            >
                <option v-for="opt in outcomeOptions" :key="opt" :value="opt">
                    {{ opt }}
                </option>
            </ui-select>

            <ui-select
                v-if="outcome === 'Closed'"
                v-model="reason"
                label="Reason"
                placeholder="Select a reason"
                :error="store.outcomeError"
            >
                <option value="ED Visit">ED Visit</option>
                <option value="Opened by mistake">Opened by mistake</option>
                <option value="Transfer">Transfer</option>
            </ui-select>

            <div v-if="showSubmit">
                <ui-button @click="submit">Submit</ui-button>
            </div>

            <!-- The Reason select above shows its own error via `:error` for
                 the Closed+empty-reason case; this covers everything else
                 submitOutcome/submitDischarge/... can fail with (illegal
                 transition, a 409/422 from the server), which has no field
                 of its own to attach to. -->
            <p
                v-if="store.outcomeError && outcome !== 'Closed'"
                class="outcome-bar__error"
            >
                {{ store.outcomeError }}
            </p>
        </div>

        <TransferFields v-if="isTransfer" />
        <DischargeFields v-else-if="outcome === 'Discharge'" />
        <ReadyForReviewFields v-else-if="outcome === 'Ready for Review'" />
        <ReadyForNextStepsFields
            v-else-if="outcome === 'Ready for Next Steps'"
        />
        <CompletedFields v-else-if="outcome === 'Completed'" />
    </div>
</template>

<script setup>
// Outcome/Reason transitions per AOC-4472 (Open/In Progress/Closed) and the
// discharge lifecycle (Discharge/Ready for Review/Ready for Next
// Steps/Completed). `Transfer` (Reason, Closed outcome) submits through
// TransferFields, like the discharge-lifecycle outcomes do.
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { UiSelect, UiButton, UiAlert } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import DischargeFields from './DischargeFields.vue'
import ReadyForReviewFields from './ReadyForReviewFields.vue'
import ReadyForNextStepsFields from './ReadyForNextStepsFields.vue'
import CompletedFields from './CompletedFields.vue'
import TransferFields from './TransferFields.vue'

const store = useHospitalizationTracker()

const outcome = ref('')
const reason = ref('')

// The discharge-lifecycle outcomes (Discharge/Ready for Review/Ready for Next
// Steps/Completed) submit through their own Fields component, which this bar
// never calls back into — so a successful transition has no other way to
// clear the picked outcome. The tracker's status changing is the one signal
// every outcome (including In Progress/Closed) shares, so resetting on it
// covers all of them the same way: the dropdown goes back to unselected and
// its Fields component unmounts, instead of staying on screen showing what
// was just submitted. A transfer swaps in a new tracker that is In Progress
// like the one it replaced, so its status alone would not change — the tracker
// id is watched too.
watch(
    () => [store.tracker?.tracker_id, store.tracker?.status],
    () => {
        outcome.value = ''
        reason.value = ''
    },
)

// A picked outcome is a transition that has not been submitted yet.
watch(
    () => outcome.value !== '',
    (picked) => store.setUnsaved('outcome', picked),
)
onBeforeUnmount(() => store.setUnsaved('outcome', false))

const showBar = computed(
    () =>
        store.tracker?.status !== 'Closed' &&
        store.tracker?.status !== 'Completed',
)

const outcomeOptions = computed(() => {
    switch (store.tracker?.status) {
        case 'Open':
            return ['In Progress', 'Closed']
        case 'In Progress':
            return ['Discharge', 'Closed']
        case 'Discharged':
            return ['Ready for Review']
        case 'Ready for Review':
            return ['Ready for Next Steps']
        case 'Ready for Next Steps':
            return ['Completed']
        default:
            return []
    }
})

const isTransfer = computed(
    () => outcome.value === 'Closed' && reason.value === 'Transfer',
)

// A transfer has its own Submit, inside TransferFields, next to the fields it sends.
const showSubmit = computed(
    () =>
        outcome.value === 'In Progress' ||
        (outcome.value === 'Closed' && !isTransfer.value),
)

// `admission_at` is the real backend contract's key for this field (Current
// Hospitalization Info's Admission Date/Time — HospitalizationTrackerFieldEnum::
// ADMISSION_AT), stored as `M/D/YYYY h:mm AM/PM` (12h clock) per that field's
// dateFormat(), matching UiDateTimepicker's `datetime` mode format string.
function parseAdmissionDateTime(value) {
    const match =
        /^(\d{1,2})\/(\d{1,2})\/(\d{4}) (\d{1,2}):(\d{2}) (AM|PM)$/.exec(
            value ?? '',
        )
    if (!match) return null
    const [, month, day, year, rawHours, minutes, meridiem] = match
    let hours = Number(rawHours) % 12
    if (meridiem === 'PM') hours += 12
    return new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
        hours,
        Number(minutes),
    )
}

// AC4/AC5 scope this to an Open tracker specifically ("Given a hospitalization
// tracker has status 'Open'") — once it's In Progress the banner's "select the
// proper outcome below" prompt no longer applies (there's no more "haven't
// picked an outcome yet" for an Open tracker to nudge about).
const showAdmissionBanner = computed(() => {
    if (store.tracker?.status !== 'Open') return false
    const parsed = parseAdmissionDateTime(store.tracker?.admission_at)
    if (!parsed) return false
    const ageHours = (Date.now() - parsed.getTime()) / 36e5
    return ageHours > 24
})

function submit() {
    store.submitOutcome({ outcome: outcome.value, reason: reason.value })
}
</script>

<style scoped>
.outcome-bar {
    margin: 12px 0;
}
.outcome-bar__banner {
    margin-bottom: 12px;
}
.outcome-bar__controls {
    display: flex;
    align-items: flex-end;
    gap: 8px;
}
.outcome-bar__error {
    margin: 4px 0 0;
    font-size: 12px;
    color: #dc3545;
}
</style>
