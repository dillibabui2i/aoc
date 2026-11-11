<template>
    <ui-modal
        :model-value="store.diagnosisModalOpen"
        title="Diagnosis Details"
        css-class="vbc-modal diagnosis-details-modal"
        @update:model-value="store.closeDiagnosisModal"
    >
        <div v-if="isReadOnly" class="diagnosis-details__readonly">
            <div class="diagnosis-details__row">
                <div>
                    <strong>Diagnosis Code</strong>
                    <p>
                        {{ store.editingDiagnosis?.icd_10_code }} —
                        {{ store.editingDiagnosis?.description }}
                    </p>
                </div>
                <div>
                    <strong>Rank</strong>
                    <p>{{ store.editingDiagnosis?.rank_label }}</p>
                </div>
            </div>
            <div class="diagnosis-details__row">
                <div>
                    <strong>System</strong>
                    <p>{{ store.editingDiagnosis?.system_label }}</p>
                </div>
                <div>
                    <strong>Clinical Status</strong>
                    <p>{{ store.editingDiagnosis?.clinical_status_label }}</p>
                </div>
                <div>
                    <strong>Verification Status</strong>
                    <p>{{ store.editingDiagnosis?.verification_status_label }}</p>
                </div>
            </div>
            <div class="diagnosis-details__row">
                <div>
                    <strong>Creation Date</strong>
                    <p>{{ store.editingDiagnosis?.created_at }}</p>
                </div>
                <div>
                    <strong>Author</strong>
                    <p>{{ store.editingDiagnosis?.author }}</p>
                </div>
            </div>
            <div>
                <strong>Diagnosis Notes</strong>
                <p>{{ store.editingDiagnosis?.notes }}</p>
            </div>
        </div>

        <div v-else class="diagnosis-details__form">
            <div class="diagnosis-details__row">
                <IcdCodeSearch
                    v-model:code="form.icd_10_code"
                    v-model:description="form.description"
                    :error="
                        errors.icd_10_code ? 'Diagnosis Code is required.' : null
                    "
                />
                <ui-select
                    v-model="form.rank"
                    label="Rank"
                    placeholder="Select..."
                    :error="errors.rank ? 'Rank is required.' : null"
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in DIAGNOSIS_RANK_OPTIONS"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
            </div>

            <div class="diagnosis-details__row">
                <ui-select
                    v-model="form.system"
                    label="System"
                    placeholder="Select..."
                    :error="errors.system ? 'System is required.' : null"
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in DIAGNOSIS_SYSTEM_OPTIONS"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
                <ui-select
                    v-model="form.clinical_status"
                    label="Clinical Status"
                    placeholder="Select..."
                    :error="
                        errors.clinical_status
                            ? 'Clinical Status is required.'
                            : null
                    "
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in DIAGNOSIS_CLINICAL_STATUS_OPTIONS"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
                <ui-select
                    v-model="form.verification_status"
                    label="Verification Status"
                    placeholder="Select..."
                    :error="
                        errors.verification_status
                            ? 'Verification Status is required.'
                            : null
                    "
                >
                    <option value="">Select...</option>
                    <option
                        v-for="opt in DIAGNOSIS_VERIFICATION_STATUS_OPTIONS"
                        :key="opt.value"
                        :value="opt.value"
                    >
                        {{ opt.label }}
                    </option>
                </ui-select>
            </div>

            <!-- Creation Date/Author are stamped by the backend (AC: system-set, never client input) —
                 they don't exist yet while creating, so this row only appears once editing an existing
                 diagnosis (store.editingDiagnosis set), matching the design's create-vs-edit split. -->
            <div v-if="store.editingDiagnosis" class="diagnosis-details__row">
                <ui-input
                    label="Creation Date"
                    :model-value="store.editingDiagnosis?.created_at"
                    disabled
                />
                <ui-input
                    label="Author"
                    :model-value="store.editingDiagnosis?.author"
                    disabled
                />
            </div>

            <ui-textarea v-model="form.notes" label="Diagnosis Notes" />
        </div>

        <template #footer>
            <ui-button v-if="!isReadOnly" @click="submit">Submit</ui-button>
            <ui-button @click="store.closeDiagnosisModal">Close</ui-button>
        </template>
    </ui-modal>
</template>

<script setup>
// Diagnosis creation/editing (AC9-AC15) and read-only display once the tracker leaves "In Progress"
// (AC16). Mirrors ContactDetailsModal.vue's one-modal, three-mode pattern. Field keys match the backend
// contract 1:1 (icd_10_code/description/system/rank/clinical_status/verification_status/notes) — no
// payload mapping layer, unlike Contacts. Creation Date/Author are system-set: shown disabled once
// editing an existing diagnosis (store.editingDiagnosis), never shown while creating one (nothing to
// show yet) and never part of the submitted payload either way. System IS part of the form (BA:
// required, highlighted red like the rest) even though today it only ever has one selectable value
// (ICD-10) — see DIAGNOSIS_SYSTEM_OPTIONS.
import { reactive, computed, watch } from 'vue'
import {
    UiModal,
    UiInput,
    UiSelect,
    UITextArea as UiTextarea,
    UiButton,
} from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import {
    DIAGNOSIS_SYSTEM_OPTIONS,
    DIAGNOSIS_RANK_OPTIONS,
    DIAGNOSIS_CLINICAL_STATUS_OPTIONS,
    DIAGNOSIS_VERIFICATION_STATUS_OPTIONS,
} from '@/modules/vbc/config/hospitalizationTrackerFields.js'
import IcdCodeSearch from '@/modules/vbc/components/HospitalizationTracker/IcdCodeSearch.vue'

const store = useEdVisitTracker()

const isReadOnly = computed(() => store.tracker?.status !== 'In Progress')

function blankForm() {
    return {
        icd_10_code: '',
        description: '',
        system: '',
        rank: '',
        clinical_status: '',
        verification_status: '',
        notes: '',
    }
}

const form = reactive(blankForm())

const errors = reactive({
    icd_10_code: false,
    system: false,
    rank: false,
    clinical_status: false,
    verification_status: false,
})

// Re-fills the form whenever the modal is (re)opened, for both the create (blank) and edit (prefilled
// from `editingDiagnosis`) cases.
watch(
    () => store.diagnosisModalOpen,
    (open) => {
        if (!open) return
        Object.assign(form, blankForm(), store.editingDiagnosis ?? {})
        // A diagnosis saved with no notes comes back as `notes: null` (nullable DB column) — UiTextarea's
        // modelValue prop is String-typed with a default that only kicks in for `undefined`, not `null`,
        // so leaving it null crashes the textarea's render (`null.length`) and the field becomes uneditable.
        form.notes = form.notes ?? ''
        Object.keys(errors).forEach((key) => (errors[key] = false))
    },
)

function validate() {
    errors.icd_10_code = !form.icd_10_code || !form.description
    errors.system = !form.system
    errors.rank = !form.rank
    errors.clinical_status = !form.clinical_status
    errors.verification_status = !form.verification_status
    return !Object.values(errors).some(Boolean)
}

function submit() {
    if (!validate()) return
    store.submitDiagnosis({ ...form })
}
</script>

<style scoped>
.diagnosis-details__form,
.diagnosis-details__readonly {
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.diagnosis-details__row {
    display: flex;
    gap: 12px;
}
.diagnosis-details__row > * {
    flex: 1;
}
.diagnosis-details__readonly p {
    margin: 2px 0 0;
}
</style>
