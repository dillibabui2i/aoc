<template>
    <div class="education-tab">
        <div class="education-tab__toggle">
            <ToggleSwitch
                :model-value="!!store.tracker?.staff_family_training_required"
                :disabled="!isInProgress"
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
            :rows="records"
            :total="records.length"
            :page="1"
            :per-page="records.length || 1"
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

        <EdEducationDetailsModal />
    </div>
</template>

<script setup>
// ED Visit Education tab (PD-3973) — ED copy of HospitalizationTracker/EducationTab.vue, backed by the
// real Training/Education endpoints (records + toggle come with the tracker payload). Toggle and Add button
// are only active while the tracker is In Progress; records stay visible otherwise, and the calendar icon
// then opens the record read-only (EdEducationDetailsModal), same as Hospitalization.
import { computed } from 'vue'
import { UiTable, UiButton } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'
import EdEducationDetailsModal from './EdEducationDetailsModal.vue'

const store = useEdVisitTracker()

const records = computed(() => store.tracker?.education_records ?? [])

const isInProgress = computed(() => store.tracker?.status === 'In Progress')

const showTable = computed(() => !!store.tracker?.staff_family_training_required)

const showAddButton = computed(
    () => !!store.tracker?.staff_family_training_required && isInProgress.value,
)

function onToggle(value) {
    store.setStaffFamilyTrainingRequired(value)
}

const columns = [
    { key: 'is_completed', label: 'Completed?' },
    { key: 'training_type_name', label: 'Training Type' },
    {
        key: 'notes',
        label: 'Training/Education to be prepared for staff and family',
    },
    { key: 'action', label: 'Action' },
]
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
