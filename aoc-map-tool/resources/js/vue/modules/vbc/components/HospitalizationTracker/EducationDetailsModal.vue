<template>
    <ui-modal
        :model-value="store.educationModalOpen"
        title="Training / Education"
        css-class="vbc-modal education-details-modal"
        @update:model-value="store.closeEducationModal"
    >
        <div v-if="isReadOnly" class="education-details__readonly">
            <div>
                <strong>Training Type</strong>
                <p>{{ store.editingEducationRecord?.training_type_name }}</p>
            </div>
            <div>
                <strong>Education Source</strong>
                <p>{{ store.editingEducationRecord?.training_source_name }}</p>
            </div>
            <div>
                <strong>Education Notes</strong>
                <p>{{ store.editingEducationRecord?.notes }}</p>
            </div>
            <div class="education-details__toggle education-details__toggle--readonly">
                <label>Training Completed</label>
                <ToggleSwitch
                    :model-value="!!store.editingEducationRecord?.is_completed"
                    disabled
                />
            </div>
        </div>

        <div v-else class="education-details__form">
            <ui-select
                v-model="form.training_type_id"
                label="Training Type"
                placeholder="Select..."
                :error="
                    errors.training_type_id
                        ? 'Training Type is required.'
                        : null
                "
            >
                <option value="">Select...</option>
                <option
                    v-for="opt in trainingTypeOptions"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </ui-select>

            <ui-select
                v-model="form.training_source_id"
                label="Education Source"
                placeholder="Select..."
                :error="
                    errors.training_source_id
                        ? 'Education Source is required.'
                        : null
                "
            >
                <option value="">Select...</option>
                <option
                    v-for="opt in trainingSourceOptions"
                    :key="opt.value"
                    :value="opt.value"
                >
                    {{ opt.label }}
                </option>
            </ui-select>

            <ui-textarea v-model="form.notes" label="Education Notes" />

            <div class="education-details__toggle">
                <label>Training Completed</label>
                <ToggleSwitch v-model="form.is_completed" />
            </div>
        </div>

        <template #footer>
            <ui-button :disabled="isReadOnly" @click="submit">Submit</ui-button>
            <ui-button @click="store.closeEducationModal">Close</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Training/Education record creation/editing and read-only display once
// the tracker leaves "In Progress" (design.md Decision 2). Mirrors
// ContactDetailsModal.vue/DiagnosisDetailsModal.vue's one-modal, three-mode
// pattern. Unlike Diagnosis/Sick Plan, no Creation Date/Author fields
// appear here — none are shown in the Figma reference (design.md Decision 4).
import { reactive, computed, ref, watch } from 'vue'
import {
    UiModal,
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import ToggleSwitch from './ToggleSwitch.vue'

const store = useHospitalizationTracker()

const isReadOnly = computed(() => store.tracker?.status !== 'In Progress')

// Options fetched once, lazily, the first time the modal actually opens — mirrors
// ContactDetailsModal's `contactTypeOptions`, since this component is also mounted (but not opened)
// just by rendering the Education tab.
const trainingTypeOptions = ref([])
const trainingSourceOptions = ref([])
let optionsLoaded = false

function blankForm() {
    return {
        training_type_id: '',
        training_source_id: '',
        notes: '',
        is_completed: false,
    }
}

const form = reactive(blankForm())

const errors = reactive({
    training_type_id: false,
    training_source_id: false,
})

// Re-fills the form whenever the modal is (re)opened, for both the create
// (blank) and edit (prefilled from `editingEducationRecord`) cases.
// `training_type_name`/`training_source_name` are the labels (for the table/read-only
// view) — the dropdowns' `<option>` values are ids, so `training_type_id`/`training_source_id` (also on
// the resource) are what actually pre-select the right ones.
watch(
    () => store.educationModalOpen,
    async (open) => {
        if (!open) return
        if (!optionsLoaded) {
            optionsLoaded = true
            trainingTypeOptions.value =
                await HospitalizationTrackerService.getTrainingTypes()
            trainingSourceOptions.value =
                await HospitalizationTrackerService.getTrainingSources()
        }

        Object.assign(form, blankForm(), store.editingEducationRecord ?? {})
        if (store.editingEducationRecord) {
            // `notes` comes back `null` (not `''`) for a record saved without notes (nullable
            // column) — `UITextArea`'s `v-model` calls `.length` on it internally, which throws on
            // `null`. Same gotcha already documented in SickPlanDetailsModal.vue/ContactDetailsModal.vue.
            form.notes = store.editingEducationRecord.notes ?? ''
        }
        Object.keys(errors).forEach((key) => (errors[key] = false))
    },
)

function validate() {
    errors.training_type_id = !form.training_type_id
    errors.training_source_id = !form.training_source_id
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    store.submitEducationRecord({ ...form })
}
</script>

<style scoped lang="scss">
@use '@/modules/vbc/styles/tokens' as *;

.education-details__form,
.education-details__readonly {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.education-details__row {
    display: flex;
    gap: 12px;
}
.education-details__row > * {
    flex: 1;
}
.education-details__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
}
.education-details__readonly p {
    margin: 2px 0 0;
}
.education-details__toggle--readonly label {
    color: $text-muted;
}
</style>
