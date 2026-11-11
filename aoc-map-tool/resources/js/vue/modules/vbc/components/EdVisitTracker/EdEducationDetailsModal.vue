<template>
    <ui-modal
        :model-value="store.educationModalOpen"
        title="Training / Education"
        size="w60"
        @update:model-value="store.closeEducationModal"
    >
        <div v-if="isReadOnly" class="education-details__readonly">
            <div class="education-details__row">
                <div>
                    <strong>Training Type</strong>
                    <p>{{ store.editingEducationRecord?.training_type_name }}</p>
                </div>
                <div>
                    <strong>Education Source</strong>
                    <p>{{ store.editingEducationRecord?.training_source_name }}</p>
                </div>
            </div>
            <div>
                <strong>Education Notes</strong>
                <p>{{ store.editingEducationRecord?.notes }}</p>
            </div>
            <div>
                <strong>Training Completed</strong>
                <p>
                    {{ store.editingEducationRecord?.is_completed ? 'Yes' : 'No' }}
                </p>
            </div>
        </div>

        <div v-else class="education-details__form">
            <ui-select
                v-model="form.training_type_id"
                label="Training Type"
                placeholder="Select..."
                :error="errors.training_type_id ? 'This field is required.' : null"
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
                    errors.training_source_id ? 'This field is required.' : null
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

            <ui-textarea
                v-model="form.notes"
                label="Education Notes"
                :error="errors.notes ? 'This field is required.' : null"
            />

            <div class="education-details__toggle">
                <ToggleSwitch v-model="form.is_completed" />
                <label>Training Completed</label>
            </div>
        </div>

        <template #footer>
            <ui-button variant="secondary" @click="store.closeEducationModal"
                >Close</ui-button
            >
            <ui-button v-if="!isReadOnly" @click="submit">Submit</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// ED Visit Training/Education modal (PD-3973) — ED copy of HospitalizationTracker/EducationDetailsModal.vue.
// Create/edit while In Progress, read-only otherwise. All fields are required (Training Completed defaults Off).
import { reactive, computed, ref, watch } from 'vue'
import {
    UiModal,
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'

const store = useEdVisitTracker()

const isReadOnly = computed(() => store.tracker?.status !== 'In Progress')

// Options come from the backend, fetched once the first time the modal opens (the component is mounted,
// but not opened, just by rendering the tab).
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
    notes: false,
})

// Re-fills the form whenever the modal is (re)opened, for both the
// create (blank) and edit (prefilled from `editingEducationRecord`) cases.
watch(
    () => store.educationModalOpen,
    async (open) => {
        if (!open) return
        if (!optionsLoaded) {
            optionsLoaded = true
            try {
                trainingTypeOptions.value =
                    await EdVisitTrackerService.getTrainingTypes()
                trainingSourceOptions.value =
                    await EdVisitTrackerService.getTrainingSources()
            } catch (e) {
                optionsLoaded = false
                throw e
            }
        }

        const record = store.editingEducationRecord
        Object.assign(form, blankForm(), record ?? {})
        // `notes` may be null for a record saved without notes; the textarea needs a string.
        form.notes = record?.notes ?? ''
        Object.keys(errors).forEach((key) => (errors[key] = false))
    },
)

function validate() {
    errors.training_type_id = !form.training_type_id
    errors.training_source_id = !form.training_source_id
    errors.notes = !form.notes?.trim()
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    store.submitEducationRecord({
        training_type_id: form.training_type_id,
        training_source_id: form.training_source_id,
        notes: form.notes,
        is_completed: form.is_completed,
    })
}
</script>

<style scoped>
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
</style>
