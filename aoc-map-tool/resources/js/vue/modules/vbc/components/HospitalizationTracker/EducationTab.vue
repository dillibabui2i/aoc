<template>
    <div class="education-tab">
        <div class="education-tab__toggle">
            <ToggleSwitch
                :model-value="!!store.tracker?.staff_family_training_required"
                :disabled="!isEditable"
                @update:model-value="onToggle"
            />
            <label>Staff / Family Training/Education required?</label>
        </div>

        <ui-button
            v-if="showAddButton"
            class="education-tab__add"
            @click="store.openAddEducationRecord()"
        >
            Add Education
        </ui-button>

        <ui-table
            v-if="showTable"
            :columns="columns"
            :rows="sortedRecords"
            :total="records.length"
            :page="1"
            :per-page="records.length || 1"
            :sort-by="sortBy"
            :sort-dir="sortDir"
            @update:sort-by="sortBy = $event"
            @update:sort-dir="sortDir = $event"
        >
            <template #cell-is_completed="{ row }">
                {{ row.is_completed ? 'Yes' : 'No' }}
            </template>
            <template #cell-action="{ row }">
                <a
                    href="javascript:void(0)"
                    @click="store.openEditEducationRecord(row)"
                >
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <EducationDetailsModal />
    </div>
</template>

<script setup>
// Education tab. The toggle is persisted via its own dedicated endpoint
// (design.md Decision 1, updated 2026-10-07 — not the generic `autoSaveField`,
// which only accepts header-form fields), mirroring the Diagnosis tab's
// "Was the diagnosis made?" toggle exactly. Table/button visibility follows
// design.md Decision 3: the table is shown whenever the toggle is ON (its
// saved value persists across status changes, so existing records stay
// visible per AC11); the Add button is additionally gated on the tracker
// being "In Progress".
import { computed, ref } from 'vue'
import { UiTable, UiButton } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import ToggleSwitch from './ToggleSwitch.vue'
import EducationDetailsModal from './EducationDetailsModal.vue'

const store = useHospitalizationTracker()

const records = computed(() => store.tracker?.education_records ?? [])

const showTable = computed(() => !!store.tracker?.staff_family_training_required)

// The backend rejects every Education write (toggle included) with a 409 once the tracker leaves
// "In Progress" — see HospitalizationTrackerTrainingService::guardEditable().
const isEditable = computed(() => store.tracker?.status === 'In Progress')

const showAddButton = computed(
    () => !!store.tracker?.staff_family_training_required && isEditable.value,
)

function onToggle(value) {
    store.setStaffFamilyTrainingRequired(value)
}

const columns = [
    { key: 'is_completed', label: 'Completed?', sortable: true },
    { key: 'training_type_name', label: 'Training Type', sortable: true },
    {
        key: 'notes',
        label: 'Training/Education to be prepared for staff and family',
        sortable: true,
    },
    { key: 'action', label: 'Action' },
]

// The whole list already lives in `tracker.education_records` (no separate paginated fetch), so
// sorting happens client-side against it, same as ContactsTab.vue's sortedContacts.
const sortBy = ref(null)
const sortDir = ref('asc')

const sortedRecords = computed(() => {
    if (!sortBy.value) return records.value

    const dir = sortDir.value === 'desc' ? -1 : 1

    return [...records.value].sort((a, b) => {
        const valueA = (a[sortBy.value] ?? '').toString().toLowerCase()
        const valueB = (b[sortBy.value] ?? '').toString().toLowerCase()

        if (valueA < valueB) return -1 * dir
        if (valueA > valueB) return 1 * dir
        return 0
    })
})
</script>

<style scoped>
.education-tab__toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
}
.education-tab__add {
    margin-bottom: 12px;
}
</style>
