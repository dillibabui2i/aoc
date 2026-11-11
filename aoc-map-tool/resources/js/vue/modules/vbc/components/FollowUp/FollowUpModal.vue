<template>
    <ui-modal
        :model-value="store.modalOpen"
        title="Follow-up"
        size="w60"
        css-class="follow-up-modal"
        @update:model-value="store.closeModal"
    >
        <template #extraHeader>
            <span class="follow-up-modal__badge">{{ badgeLabel }}</span>
        </template>

        <HospitalizedClientSearch
            v-if="stage === 'search'"
            @select="store.selectClientForCreate"
        />

        <div v-else class="follow-up-modal__body">
            <div class="follow-up-modal__row">
                <div>
                    <strong>Hospitalized Client Name</strong>
                    <p>{{ header.fullName }}</p>
                </div>
            </div>
            <div class="follow-up-modal__row">
                <div>
                    <strong>Client ID</strong>
                    <p>{{ header.clientId }}</p>
                </div>
                <div>
                    <strong>Hospitalization ID</strong>
                    <p>{{ header.trackerId }}</p>
                </div>
                <div>
                    <strong>Hospitalized At</strong>
                    <p>{{ header.facilityName }}</p>
                </div>
                <div>
                    <strong>Admission Date</strong>
                    <p>{{ header.admissionDateTime }}</p>
                </div>
            </div>

            <p v-if="optionalMessage" class="follow-up-modal__message">
                {{ optionalMessage }}
            </p>

            <template v-if="stage === 'outcome'">
                <div
                    v-if="store.followUp.status === 'Scheduled'"
                    class="follow-up-modal__row"
                >
                    <div>
                        <strong>Follow-up date</strong>
                        <p>{{ store.followUp.followUpDate }}</p>
                    </div>
                    <div>
                        <strong>Follow-up assigned To</strong>
                        <p>{{ store.followUp.assignedTo }}</p>
                    </div>
                </div>

                <ui-select v-model="selectedOutcome" label="Follow-up outcome">
                    <option value="">Select an outcome</option>
                    <option
                        v-for="opt in outcomeOptions"
                        :key="opt"
                        :value="opt"
                    >
                        {{ opt }}
                    </option>
                </ui-select>

                <div
                    v-if="selectedOutcome === 'Scheduled'"
                    class="follow-up-modal__row"
                >
                    <ui-date-timepicker
                        v-model="form.followUpDate"
                        label="Follow-up date"
                        mode="datetime"
                        time-format="12"
                        :class="{ 'has-error': errors.followUpDate }"
                    />
                    <ui-select
                        v-model="form.assignedTo"
                        label="Follow-up assigned To"
                        :error="
                            errors.assignedTo ? 'This field is required.' : null
                        "
                    >
                        <option value="">Assign a person here</option>
                        <option
                            v-for="opt in assigneeOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </ui-select>
                </div>

                <template v-else-if="selectedOutcome === 'Completed'">
                    <div class="follow-up-modal__row">
                        <ui-input
                            label="Completion date"
                            :model-value="form.completionDate"
                            disabled
                        />
                        <ui-input
                            label="Completed by"
                            :model-value="form.completedBy"
                            disabled
                        />
                    </div>
                    <ui-textarea
                        v-model="form.statusNote"
                        label="Status *"
                        :error="
                            errors.statusNote ? 'This field is required.' : null
                        "
                    />
                    <ui-textarea
                        v-model="form.followUpsUpdates"
                        label="Follow-Ups/Updates *"
                        :error="
                            errors.followUpsUpdates
                                ? 'This field is required.'
                                : null
                        "
                    />
                    <ui-textarea
                        v-model="form.needs"
                        label="Needs *"
                        :error="errors.needs ? 'This field is required.' : null"
                    />
                    <ui-textarea
                        v-model="form.additionalNotes"
                        label="Additional Notes"
                    />

                    <div class="follow-up-modal__toggle">
                        <ToggleSwitch v-model="form.nextFollowUpDue" />
                        <label>Next follow-up due?</label>
                    </div>
                    <div
                        v-if="form.nextFollowUpDue"
                        class="follow-up-modal__row"
                    >
                        <ui-date-timepicker
                            v-model="form.nextFollowUpDueDate"
                            label="Next follow-up due date"
                            mode="datetime"
                            time-format="12"
                            :class="{
                                'has-error': errors.nextFollowUpDueDate,
                            }"
                        />
                        <ui-select
                            v-model="form.nextAssignedTo"
                            label="Next Follow-up assigned To"
                            :error="
                                errors.nextAssignedTo
                                    ? 'This field is required.'
                                    : null
                            "
                        >
                            <option value="">Select...</option>
                            <option
                                v-for="opt in assigneeOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </ui-select>
                    </div>
                </template>

                <template v-else-if="selectedOutcome === 'Attempted'">
                    <div class="follow-up-modal__row">
                        <ui-input
                            label="Attempt date"
                            :model-value="form.attemptDate"
                            disabled
                        />
                        <ui-input
                            label="Attempted by"
                            :model-value="form.attemptedBy"
                            disabled
                        />
                    </div>
                    <ui-textarea
                        v-model="form.attemptNotes"
                        label="Attempt Notes *"
                        :error="
                            errors.attemptNotes
                                ? 'This field is required.'
                                : null
                        "
                    />

                    <div class="follow-up-modal__toggle">
                        <ToggleSwitch v-model="form.nextFollowUpDue" />
                        <label>Next follow-up due?</label>
                    </div>
                    <div
                        v-if="form.nextFollowUpDue"
                        class="follow-up-modal__row"
                    >
                        <ui-date-timepicker
                            v-model="form.nextFollowUpDueDate"
                            label="Next follow-up due date"
                            mode="datetime"
                            time-format="12"
                            :class="{
                                'has-error': errors.nextFollowUpDueDate,
                            }"
                        />
                        <ui-select
                            v-model="form.nextAssignedTo"
                            label="Next Follow-up assigned To"
                            :error="
                                errors.nextAssignedTo
                                    ? 'This field is required.'
                                    : null
                            "
                        >
                            <option value="">Select...</option>
                            <option
                                v-for="opt in assigneeOptions"
                                :key="opt.value"
                                :value="opt.value"
                            >
                                {{ opt.label }}
                            </option>
                        </ui-select>
                    </div>
                </template>
            </template>

            <template v-else-if="stage === 'readonly'">
                <div class="follow-up-modal__row">
                    <div>
                        <strong>Follow-up date</strong>
                        <p>{{ store.followUp.followUpDate }}</p>
                    </div>
                    <div>
                        <strong>Follow-up assigned To</strong>
                        <p>{{ store.followUp.assignedTo }}</p>
                    </div>
                </div>

                <template v-if="store.followUp.status === 'Completed'">
                    <div class="follow-up-modal__row">
                        <div>
                            <strong>Completion date</strong>
                            <p>{{ store.followUp.completionDate }}</p>
                        </div>
                        <div>
                            <strong>Completed by</strong>
                            <p>{{ store.followUp.completedBy }}</p>
                        </div>
                    </div>
                    <div>
                        <strong>Status</strong>
                        <p>{{ store.followUp.statusNote }}</p>
                    </div>
                    <div>
                        <strong>Follow-Ups/Updates</strong>
                        <p>{{ store.followUp.followUpsUpdates }}</p>
                    </div>
                    <div>
                        <strong>Needs</strong>
                        <p>{{ store.followUp.needs }}</p>
                    </div>
                    <div>
                        <strong>Additional Notes</strong>
                        <p>{{ store.followUp.additionalNotes }}</p>
                    </div>
                </template>

                <template v-else-if="store.followUp.status === 'Attempted'">
                    <div class="follow-up-modal__row">
                        <div>
                            <strong>Attempt date</strong>
                            <p>{{ store.followUp.attemptDate }}</p>
                        </div>
                        <div>
                            <strong>Attempted by</strong>
                            <p>{{ store.followUp.attemptedBy }}</p>
                        </div>
                    </div>
                    <div>
                        <strong>Attempt Notes</strong>
                        <p>{{ store.followUp.attemptNotes }}</p>
                    </div>
                </template>
            </template>
        </div>

        <template #footer>
            <ui-button variant="secondary" @click="store.closeModal"
                >Close</ui-button
            >
            <ui-button v-if="stage === 'new-confirmed'" @click="saveNew">
                Submit
            </ui-button>
            <ui-button
                v-else-if="stage === 'outcome' && selectedOutcome"
                @click="submitOutcomeForm"
            >
                Submit
            </ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Follow-up modal (Hospitalization Follow-ups story). Unlike every other
// tab modal in this codebase, this one never auto-closes on submit — it
// stays open and re-renders the next stage until the user explicitly
// closes it (design.md Decision 1). Editability/stage is a pure function
// of `store.followUp.status`, not the hospitalization tracker's own
// status (design.md Decision 3).
import { ref, reactive, computed, watch, onMounted } from 'vue'
import {
    UiModal,
    UiInput,
    UiSelect,
    UiDateTimepicker,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import HospitalizedClientSearch from './HospitalizedClientSearch.vue'
import ToggleSwitch from '../HospitalizationTracker/ToggleSwitch.vue'

const store = useFollowUp()

// Real "Assigned To" options (users holding the Intensive Case Management permission) — the same
// endpoint the Hospitalization Record form's own header field uses.
const assigneeOptions = ref([])
onMounted(async () => {
    assigneeOptions.value = await HospitalizationTrackerService.getAssignees()
})

const READONLY_STATUSES = ['Completed', 'Attempted', 'Canceled']
const QUALIFYING_LEVELS = ['Level I/III', 'Level II/III', 'Level III']

const stage = computed(() => {
    if (store.pendingClient && !store.followUp) return 'new-confirmed'
    if (!store.followUp) return 'search'
    if (READONLY_STATUSES.includes(store.followUp.status)) return 'readonly'
    return 'outcome'
})

const badgeLabel = computed(() => store.followUp?.status ?? 'New')

const header = computed(() => {
    const source = store.followUp ?? store.pendingClient
    if (!source) return {}
    return {
        fullName: source.clientName ?? source.fullName,
        clientId: source.clientId,
        trackerId: source.trackerId,
        facilityName: source.facilityName,
        admissionDateTime: source.admissionDateTime,
    }
})

function daysSince(dateString) {
    if (!dateString) return 0
    const then = new Date(dateString).getTime()
    if (Number.isNaN(then)) return 0
    return (Date.now() - then) / (1000 * 60 * 60 * 24)
}

const OPTIONAL_MESSAGE_RULES = [
    { level: 'Level I/III', days: 30 },
    { level: 'Level II/III', days: 60 },
    { level: 'Level III', days: 90 },
]

// Design.md Decision 11: computed only from `pendingClient` (the New,
// pre-first-save stage) — never re-shown once the follow-up is saved.
const optionalMessage = computed(() => {
    const client = store.pendingClient
    if (!client) return null
    const rule = OPTIONAL_MESSAGE_RULES.find((r) => r.level === client.level)
    if (!rule) return null
    if (daysSince(client.admissionDateTime) <= rule.days) return null
    return `More than ${rule.days} days have passed since the admission date, and based on this patient's level, this follow-up will be optional.`
})

// Design.md Decision 7: "Scheduled" dropped once already Scheduled.
const outcomeOptions = computed(() => {
    if (!store.followUp) return []
    if (store.followUp.status === 'Open') {
        return ['Scheduled', 'Completed', 'Attempted', 'Canceled']
    }
    if (store.followUp.status === 'Scheduled') {
        return ['Completed', 'Attempted', 'Canceled']
    }
    return []
})

function formatDateTime(date) {
    const pad = (n) => String(n).padStart(2, '0')
    return `${pad(date.getMonth() + 1)}/${pad(date.getDate())}/${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function blankForm() {
    return {
        followUpDate: '',
        assignedTo: '',
        completionDate: '',
        completedBy: '',
        statusNote: '',
        followUpsUpdates: '',
        needs: '',
        additionalNotes: '',
        attemptDate: '',
        attemptedBy: '',
        attemptNotes: '',
        nextFollowUpDue: false,
        nextFollowUpDueDate: '',
        nextAssignedTo: '',
    }
}

const form = reactive(blankForm())
const selectedOutcome = ref('')

const errors = reactive({
    followUpDate: false,
    assignedTo: false,
    statusNote: false,
    followUpsUpdates: false,
    needs: false,
    attemptNotes: false,
    nextFollowUpDueDate: false,
    nextAssignedTo: false,
})

function resetErrors() {
    Object.keys(errors).forEach((key) => (errors[key] = false))
}

// Design.md Decision 9: default Next Follow-up Due based on the current
// follow-up's own hospitalization Level, seeding the date from the
// follow-up's own scheduled date/time (falling back to "now").
function applyNextFollowUpDueDefault() {
    const qualifies = QUALIFYING_LEVELS.includes(store.followUp?.level)
    form.nextFollowUpDue = qualifies
    if (!qualifies) {
        form.nextFollowUpDueDate = ''
        return
    }
    const base = store.followUp?.followUpDate
        ? new Date(store.followUp.followUpDate)
        : new Date()
    const due = new Date(base.getTime() + 7 * 24 * 60 * 60 * 1000)
    form.nextFollowUpDueDate = formatDateTime(due)
}

// Completion/Attempt Date and By are previewed as "now" locally, but are authoritative only once the
// server sets them on submit (it stamps its own now()/auth()->id(), not whatever this preview shows).
watch(selectedOutcome, (outcome) => {
    Object.assign(form, blankForm())
    resetErrors()
    if (outcome === 'Completed') {
        form.completionDate = new Date().toLocaleString('en-US')
        applyNextFollowUpDueDefault()
    } else if (outcome === 'Attempted') {
        form.attemptDate = new Date().toLocaleString('en-US')
        applyNextFollowUpDueDefault()
    }
})

// Resets local form/selection whenever the modal is (re)opened.
watch(
    () => store.modalOpen,
    (open) => {
        if (!open) return
        Object.assign(form, blankForm())
        selectedOutcome.value = ''
        resetErrors()
    },
)

// The modal stays open across a successful outcome submission (it never auto-closes — only the
// explicit Close/✕ does). Without this, the just-submitted outcome's own edit fields (e.g. Scheduled's
// date+assignee) kept showing their old values underneath the new read-only preview, because
// `selectedOutcome` no longer matched any option in the new `outcomeOptions` list but was still truthy.
watch(
    () => store.followUp?.status,
    () => {
        Object.assign(form, blankForm())
        selectedOutcome.value = ''
        resetErrors()
    },
)

function saveNew() {
    store.submitCreate()
}

function validate() {
    resetErrors()
    if (selectedOutcome.value === 'Scheduled') {
        errors.followUpDate = !form.followUpDate
        errors.assignedTo = !form.assignedTo
    } else if (selectedOutcome.value === 'Completed') {
        errors.statusNote = !form.statusNote
        errors.followUpsUpdates = !form.followUpsUpdates
        errors.needs = !form.needs
        if (form.nextFollowUpDue) {
            errors.nextFollowUpDueDate = !form.nextFollowUpDueDate
            errors.nextAssignedTo = !form.nextAssignedTo
        }
    } else if (selectedOutcome.value === 'Attempted') {
        errors.attemptNotes = !form.attemptNotes
        if (form.nextFollowUpDue) {
            errors.nextFollowUpDueDate = !form.nextFollowUpDueDate
            errors.nextAssignedTo = !form.nextAssignedTo
        }
    }
    return !Object.values(errors).some(Boolean)
}

function submitOutcomeForm() {
    if (!validate()) return
    store.submitOutcome(selectedOutcome.value, { ...form })
}
</script>

<style scoped>
.follow-up-modal__badge {
    margin-left: 10px;
    padding: 3px 12px;
    border-radius: 999px;
    background: #dbeafe;
    color: #1d4ed8;
    font-size: 12px;
    font-weight: 600;
}
.follow-up-modal__body {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.follow-up-modal__row {
    display: flex;
    gap: 12px;
}
.follow-up-modal__row > * {
    flex: 1;
}
.follow-up-modal__message {
    background: #eff6ff;
    color: #1d4ed8;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 13px;
}
.follow-up-modal__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
}
/* `UiDateTimepicker`'s frame now lives on the nested `.ui-datepicker__row`,
   not `.ui-datepicker-wrap` itself (v0.5.9+) — the error border has to land
   there, or it's invisible. */
:deep(.has-error.ui-datepicker-wrap .ui-datepicker__row) {
    border-color: #dc2626;
}

/* `UiDateTimepicker`'s own label renders inline, overlapping its input's top border (a legend, not a
   stacked label above the box); `UiSelect`'s label sits on its own line above the box instead. Paired
   in the same row (Follow-up date + Follow-up assigned To, Next follow-up due date + Next follow-up
   assigned To), that mismatch leaves the two boxes' top edges misaligned — this pushes the date field
   down by the select's label-line height so both input boxes line up. */
.follow-up-modal__row :deep(.ui-datepicker-wrap) {
    margin-top: 30px;
}
</style>

<style>
/* UiModal teleports to <body>, so this modal's own layout rules can't be scoped (see
   HospitalizationTrackerModal.vue for the same pattern). The Completed/Attempted outcome adds enough
   fields that the dialog can exceed the viewport — without an explicit cap + scroll, the bottom of the
   form (Additional Notes, Submit) is simply unreachable. */
.follow-up-modal .modal-content {
    max-height: 90vh;
    overflow-y: auto;
}
</style>
