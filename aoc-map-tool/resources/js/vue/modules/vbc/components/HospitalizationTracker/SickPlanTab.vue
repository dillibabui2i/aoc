<template>
    <div class="sick-plan-tab">
        <ui-table
            :columns="columns"
            :rows="items"
            :total="items.length"
            :page="1"
            :per-page="items.length || 1"
            :show-per-page="false"
        >
            <template #cell-completed="{ row }">
                {{ row.completed ? 'Yes' : 'No' }}
            </template>
            <template #cell-completedBy="{ row }">
                {{ row.completedBy ?? '-' }}
            </template>
            <template #cell-action="{ row }">
                <a
                    href="javascript:void(0)"
                    @click="store.openEditSickPlanItem(row)"
                >
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <SickPlanDetailsModal />
    </div>
</template>

<script setup>
// Sick Plan tab (AC4/AC6-AC9). The calendar icon always opens the modal, which
// itself decides editable vs. read-only. The Sick Plan itself belongs to
// the client (design.md Decision 1 of that story) — `tracker.sickPlan` is
// hydrated by the service, so this component reads it the same way
// ContactsTab/DiagnosisTab read their tracker-scoped lists.
//
// TrackerTabs only renders the active tab's slot content, so this component is torn down and
// re-mounted every time the user switches to it — re-fetching here on mount is what makes a Sick
// Plan created earlier in the same session (e.g. the blank plan from opening this tracker) show up
// without a full page reload.
import { computed, onMounted } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import SickPlanDetailsModal from './SickPlanDetailsModal.vue'

const store = useHospitalizationTracker()

const items = computed(() => store.tracker?.sickPlan?.items ?? [])

onMounted(() => store.loadSickPlan())

const columns = [
    { key: 'sickPlanType', label: 'Sick Plan Type' },
    { key: 'completed', label: 'Completed?' },
    { key: 'completedBy', label: 'Completed by' },
    { key: 'notes', label: 'Notes' },
    { key: 'action', label: 'Action' },
]
</script>
