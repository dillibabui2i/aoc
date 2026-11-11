<template>
    <div class="diagnosis-tab">
        <div class="diagnosis-tab__toggle">
            <ToggleSwitch
                :model-value="!!store.tracker?.was_diagnosis_made"
                :disabled="store.tracker?.status !== 'In Progress'"
                @update:model-value="onToggle"
            />
            <label>Was the Diagnosis Made?</label>
        </div>

        <ui-button
            v-if="showAddButton"
            class="diagnosis-tab__add"
            @click="store.openAddDiagnosis()"
        >
            Add Diagnosis
        </ui-button>

        <ui-table
            v-if="showTable"
            :columns="columns"
            :rows="sortedDiagnoses"
            :total="diagnoses.length"
            :page="1"
            :per-page="diagnoses.length || 1"
            :show-per-page="false"
            :sort-by="sortBy"
            :sort-dir="sortDir"
            @update:sort-by="sortBy = $event"
            @update:sort-dir="sortDir = $event"
        >
            <template #cell-icd_10_code="{ row }">
                {{ row.icd_10_code }} — {{ row.description }}
            </template>
            <template #cell-rank="{ row }">
                {{ row.rank_label }}
            </template>
            <template #cell-clinical_status="{ row }">
                {{ row.clinical_status_label }}
            </template>
            <template #cell-verification_status="{ row }">
                {{ row.verification_status_label }}
            </template>
            <template #cell-action="{ row }">
                <a
                    href="javascript:void(0)"
                    @click="store.openEditDiagnosis(row)"
                >
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <EdDiagnosisDetailsModal />
    </div>
</template>

<script setup>
// Diagnosis tab (AC3-AC8/AC17). The toggle has its own dedicated save path (setWasDiagnosisMade) rather
// than the generic autoSaveField, since "Was the diagnosis made?" isn't a header-form field — see
// edVisitTracker.store.js (PD-3970, ported from the Hospitalization Diagnosis tab). Table/button
// visibility is a pure local computation, not stored state.
// The whole list already lives in `tracker.diagnoses` (no separate paginated fetch), so sorting happens
// client-side and the per-page "Show X Entries" control is hidden — same as ContactsTab.vue.
import { computed, ref } from 'vue'
import { UiTable, UiButton } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'
import EdDiagnosisDetailsModal from './EdDiagnosisDetailsModal.vue'

const store = useEdVisitTracker()

const diagnoses = computed(() => store.tracker?.diagnoses ?? [])

const sortBy = ref(null)
const sortDir = ref('asc')

// Sorts by each column's display label when one exists (rank_label/clinical_status_label/
// verification_status_label), not the raw enum value — alphabetical-by-code and
// alphabetical-by-label don't always agree (e.g. Verification Status's ENTERED_IN_ERROR vs
// "Entered in Error").
const sortedDiagnoses = computed(() => {
    if (!sortBy.value) return diagnoses.value

    const dir = sortDir.value === 'desc' ? -1 : 1

    return [...diagnoses.value].sort((a, b) => {
        const valueA = (a[`${sortBy.value}_label`] ?? a[sortBy.value] ?? '')
            .toString()
            .toLowerCase()
        const valueB = (b[`${sortBy.value}_label`] ?? b[sortBy.value] ?? '')
            .toString()
            .toLowerCase()

        if (valueA < valueB) return -1 * dir
        if (valueA > valueB) return 1 * dir
        return 0
    })
})

const showTable = computed(
    () => !!store.tracker?.was_diagnosis_made || diagnoses.value.length > 0,
)

const showAddButton = computed(
    () =>
        !!store.tracker?.was_diagnosis_made &&
        store.tracker?.status === 'In Progress',
)

function onToggle(value) {
    store.setWasDiagnosisMade(value)
}

const columns = [
    { key: 'icd_10_code', label: 'Diagnosis Code', sortable: true },
    { key: 'rank', label: 'Rank', sortable: true },
    { key: 'clinical_status', label: 'Clinical Status', sortable: true },
    { key: 'verification_status', label: 'Verification Status', sortable: true },
    { key: 'action', label: 'Action' },
]
</script>

<style scoped>
.diagnosis-tab__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.diagnosis-tab__add {
    margin-bottom: 12px;
}
</style>
