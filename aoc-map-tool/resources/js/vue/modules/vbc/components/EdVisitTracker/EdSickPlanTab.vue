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

        <EdSickPlanDetailsModal />
    </div>
</template>

<script setup>
// Sick Plan tab (PD-3971), ported from HospitalizationTracker/SickPlanTab.vue. The tab is torn down
// on every switch, so loading on mount picks up the plan created with the tracker.
import { computed, onMounted } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import EdSickPlanDetailsModal from './EdSickPlanDetailsModal.vue'

const store = useEdVisitTracker()

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
