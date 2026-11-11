<template>
    <ui-modal
        :model-value="store.modalOpen"
        title="Care Coordination"
        size="w60"
        css-class="vbc-modal"
        @update:model-value="store.closeModal"
    >
        <EdHospitalizedClientSearch
            v-if="isStageOneSearch"
            @select="store.confirmClientForCreate"
        />

        <div v-else class="care-coordination__form">
            <div class="care-coordination__row">
                <ui-input label="Hospitalized Client Name" :model-value="header.fullName" disabled />
            </div>
            <div class="care-coordination__row">
                <ui-input label="Client ID" :model-value="header.clientId" disabled />
                <ui-input label="Hospitalization ID" :model-value="header.trackerId" disabled />
                <ui-input label="Hospitalized At" :model-value="header.facilityName" disabled />
                <ui-input label="Admission Date" :model-value="header.admissionDateTime" disabled />
            </div>

            <template v-if="isReadOnly">
                <div class="care-coordination__row">
                    <div>
                        <strong>Coordination Type</strong>
                        <p>{{ coordinationTypeLabel }}</p>
                    </div>
                    <div>
                        <strong>Provider</strong>
                        <p>{{ form.provider }}</p>
                    </div>
                    <div>
                        <strong>Assigned To</strong>
                        <p>{{ assigneeLabel }}</p>
                    </div>
                </div>
                <div class="care-coordination__row">
                    <div>
                        <strong>Creation Date</strong>
                        <p>{{ header.createdAt }}</p>
                    </div>
                    <div>
                        <strong>Due Date</strong>
                        <p>{{ form.due_at }}</p>
                    </div>
                    <div>
                        <strong>Completion Date</strong>
                        <p>{{ form.completed_at }}</p>
                    </div>
                </div>
                <div>
                    <strong>Care Coordination Notes</strong>
                    <p>{{ form.notes }}</p>
                </div>
            </template>

            <template v-else>
                <div class="care-coordination__row">
                    <ui-select
                        v-model="form.care_coordination_type_id"
                        label="Coordination Type"
                        placeholder="Select..."
                        :error="errors.care_coordination_type_id ? 'This field is required.' : null"
                    >
                        <option value="">Select...</option>
                        <option
                            v-for="opt in coordinationTypeOptions"
                            :key="opt.value"
                            :value="opt.value"
                        >
                            {{ opt.label }}
                        </option>
                    </ui-select>
                    <ui-input v-model="form.provider" label="Provider" />
                    <ui-select
                        v-model="form.assigned_to_id"
                        label="Assigned To"
                        placeholder="Select..."
                        :error="
                            errors.assigned_to_id
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

                <div class="care-coordination__row">
                    <ui-input
                        v-if="header.createdAt"
                        label="Creation Date"
                        :model-value="header.createdAt"
                        disabled
                    />
                    <ui-date-timepicker
                        v-model="form.due_at"
                        label="Due Date"
                        mode="datetime"
                        time-format="12"
                    />
                    <ui-date-timepicker
                        v-model="form.completed_at"
                        label="Completion Date"
                        mode="datetime"
                        time-format="12"
                    />
                    <!-- Reuses `@angels-of-care/ui`'s own `.ui-input-wrap`/`.ui-input__label` classes (an
                         empty label as a spacer) so this lines up with the label+control rhythm of its
                         `UiInput`/`UiDateTimepicker` siblings instead of being vertically centered across
                         the whole row height. -->
                    <div class="ui-input-wrap care-coordination__toggle">
                        <label class="ui-input__label">&nbsp;</label>
                        <div class="care-coordination__toggle-control">
                            <ToggleSwitch v-model="isCompleted" />
                            <span>Care Coordination Completed</span>
                        </div>
                    </div>
                </div>

                <ui-textarea
                    v-model="form.notes"
                    label="Care Coordination Notes"
                />
            </template>
        </div>

        <template #footer>
            <!-- Figma has Submit before Close, both the same filled/primary style — unlike every other
                 VBC modal's Close(secondary)-then-Submit(primary) order, kept here on purpose per this
                 modal's own design rather than changed project-wide. Shown (disabled) during the search
                 stage too: there's nothing to submit until a patient is confirmed. -->
            <ui-button
                v-if="!isReadOnly"
                :disabled="isStageOneSearch"
                @click="submit"
                >Submit</ui-button
            >
            <ui-button @click="store.closeModal">Close</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Care Coordination modal (AC3-AC13): two-stage creation (patient search, then
// coordination fields), edit (skips stage 1, header locked), and read-only
// (the linked tracker isn't 'In Progress') all share this one component.
import { reactive, computed, watch, ref } from 'vue'
import {
    UiModal,
    UiInput,
    UiSelect,
    UiDateTimepicker,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'
import EdHospitalizedClientSearch from './EdHospitalizedClientSearch.vue'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'

const store = useEdCareCoordinationActivities()

// "Coordination Type" (real backend lookup) and "Assigned To" (real "Manage ED Visits" user list) are
// fetched once, lazily, the first time the modal actually opens — not on mount, since this component
// is also mounted (but not opened) just by rendering the Care Coordination bucket / the Hospitalization
// Tracker modal's Care Coordination tab, where the user may only ever look at the list.
const coordinationTypeOptions = ref([])
const assigneeOptions = ref([])
let optionsLoaded = false

watch(
    () => store.modalOpen,
    async (open) => {
        if (!open || optionsLoaded) return
        optionsLoaded = true
        coordinationTypeOptions.value = await edCareCoordinationActivitiesService.getTypes()
        assigneeOptions.value = await EdVisitTrackerService.getAssignees()
    },
)

const isStageOneSearch = computed(
    () => !store.editingActivity && !store.confirmedClient,
)

const isReadOnly = computed(
    () => !!store.editingActivity && store.editingActivity.tracker_status !== 'In Progress',
)

// `UiInput`'s `model-value` prop is typed as String; `hospitalization_tracker_id` is a number on both
// the resource and the search result, so it's coerced here rather than at every call site.
const header = computed(() => {
    if (store.editingActivity) {
        const activity = store.editingActivity
        return {
            fullName: activity.patient_name,
            clientId: activity.patient_account,
            trackerId: asText(activity.hospitalization_tracker_id),
            facilityName: activity.facility_name,
            admissionDateTime: activity.admission_date_time,
            createdAt: activity.created_at,
        }
    }
    const client = store.confirmedClient
    return {
        fullName: client?.full_name,
        clientId: client?.patient_account,
        trackerId: asText(client?.hospitalization_tracker_id),
        facilityName: client?.facility_name,
        admissionDateTime: client?.admission_date_time,
        createdAt: null,
    }
})

function asText(value) {
    return value === null || value === undefined ? '' : String(value)
}

const coordinationTypeLabel = computed(
    () =>
        coordinationTypeOptions.value.find((opt) => opt.value === form.care_coordination_type_id)
            ?.label ?? null,
)

const assigneeLabel = computed(
    () => assigneeOptions.value.find((opt) => opt.value === form.assigned_to_id)?.label ?? null,
)

function blankForm() {
    return {
        care_coordination_type_id: '',
        provider: '',
        assigned_to_id: '',
        due_at: '',
        completed_at: '',
        notes: '',
    }
}

const form = reactive(blankForm())

const errors = reactive({
    care_coordination_type_id: false,
    assigned_to_id: false,
})

// The design shows a "Care Coordination Completed" toggle next to Completion Date, but the backend has
// no separate "completed" flag — only `completed_at`. The toggle is a convenience for that one field:
// turning it on stamps "now" if Completion Date is still empty; turning it off clears it. Completion
// Date can still be edited directly regardless of the toggle.
const isCompleted = computed({
    get: () => !!form.completed_at,
    set: (value) => {
        form.completed_at = value ? nowFormatted() : ''
    },
})

function nowFormatted() {
    const date = new Date()
    const pad = (n) => String(n).padStart(2, '0')
    const hours12 = date.getHours() % 12 || 12
    const ampm = date.getHours() < 12 ? 'AM' : 'PM'

    return `${pad(date.getMonth() + 1)}/${pad(date.getDate())}/${date.getFullYear()} ${pad(hours12)}:${pad(date.getMinutes())} ${ampm}`
}

// Re-fills the form whenever the modal is (re)opened, or once stage 1
// reveals the coordination fields.
watch(
    () => [store.modalOpen, store.editingActivity, store.confirmedClient],
    () => {
        if (!store.modalOpen) return
        // `notes` comes back `null` (not `''`) for an activity saved without notes (nullable
        // column) — `UITextArea`'s `v-model` calls `.length` on it internally, which throws on
        // `null` and crashes the field's render. Same gotcha as ContactDetailsModal.vue/
        // SickPlanDetailsModal.vue.
        Object.assign(form, blankForm(), store.editingActivity ?? {}, {
            notes: store.editingActivity?.notes ?? '',
        })
        Object.keys(errors).forEach((key) => (errors[key] = false))
    },
)

function validate() {
    errors.care_coordination_type_id = !form.care_coordination_type_id
    errors.assigned_to_id = !form.assigned_to_id
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    // `provider` stays UI-only per the ticket's scope decision — not sent to the backend, which has no
    // column for it.
    const { provider, ...fields } = form
    if (store.editingActivity) {
        store.updateActivity(fields)
    } else {
        store.createActivity(fields)
    }
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.care-coordination__form {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.care-coordination__row {
    display: flex;
    gap: 12px;
}
.care-coordination__row > * {
    flex: 1;
}
/* `.ui-input-wrap` (added alongside this class in the template) already supplies the label-then-control
   column layout that lines this up with its `UiInput`/`UiDateTimepicker` siblings — only the control row
   itself needs its own layout. */
.care-coordination__toggle-control {
    display: flex;
    align-items: center;
    gap: 8px;
    min-height: $control-height;
}
</style>
