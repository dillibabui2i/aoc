<template>
    <div>
        <TriageCareCoordinationFilters
            @search="onSearch"
            @clear="onClear"
            @add="openCreate"
        />
        <p class="vbc-bucket-info">
            <u><b>Responsible Party:</b> ICM Team</u><br />
            <b>Description:</b> Care Coordination Activities to be completed<br />
            <b>Timeline:</b> Overdue activities
        </p>
        <div v-if="store.error">{{ store.error }}</div>
        <div v-else>
            <ui-table
                :columns="columns"
                :rows="store.list"
                :row-class="rowClass"
                :loading="store.loading"
                :show-per-page="false"
                :total="store.total"
                :page="store.page"
                :per-page="store.perPage"
                :sort-by="store.sortBy"
                :sort-dir="store.sortDir"
                @update:page="store.setPage"
                @update:per-page="store.setPerPage"
                @update:sort-by="(key) => store.setSort(key, store.sortDir)"
                @update:sort-dir="(dir) => store.setSort(store.sortBy, dir)"
            >
                <template #toolbar-search>
                    <SearchInput
                        v-model="filtersStore.values.search"
                        @search="getFilteredData"
                    />
                </template>

                <template #cell-actions="{ row }">
                    <div style="white-space: nowrap">
                        <a
                            href="javascript:void(0)"
                            title="Open Care Coordination"
                            @click="openActivity(row)"
                            ><i
                                class="fa fa-calendar ml-2"
                                aria-hidden="true"
                            ></i
                        ></a>
                        <a
                            href="javascript:void(0)"
                            title="View Client Profile"
                            @click="openClientProfile(row)"
                            ><i class="fa fa-user ml-2" aria-hidden="true"></i
                        ></a>
                        <a
                            href="javascript:void(0)"
                            title="Notes"
                            @click="store.openCreateNote(row)"
                            ><i
                                class="fa fa-sticky-note-o ml-2"
                                aria-hidden="true"
                            ></i
                        ></a>
                    </div>
                </template>
            </ui-table>
        </div>
        <EdCareCoordinationModal />
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useTriageCareCoordination } from '@/modules/vbc/store/Dashboard/triageCareCoordination.store.js'
import { useTriageCareCoordinationFiltersStore } from '@/modules/vbc/store/Dashboard/triageCareCoordinationFilters.store.js'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import TriageCareCoordinationFilters from '@/modules/vbc/components/TriageCareCoordinationFilters.vue'
import EdCareCoordinationModal from '@/modules/vbc/components/EdVisitTracker/EdCareCoordinationModal.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'

const store = useTriageCareCoordination()
const filtersStore = useTriageCareCoordinationFiltersStore()
// PD-3969: the ED Care Coordination modal's lifecycle lives in its own ED store (separate from the ICM
// bucket's), so both buckets can be on one page without sharing a modal; its `onSaved` hook is what
// lets this bucket refresh its own list after a save. The ED Visit Tracker modal's Care Coordination
// tab drives the same store and leaves hosting the modal to this bucket when it is on the page.
const activityStore = useEdCareCoordinationActivities()

const columns = [
    { key: 'created_at', label: 'Created Date', sortable: true },
    { key: 'patient_name', label: 'Client Name', sortable: true },
    { key: 'patient_account', label: 'Client ID', sortable: true },
    { key: 'hospital_name', label: 'Hospital', sortable: true },
    { key: 'coordination_type', label: 'Type', sortable: true },
    { key: 'provider', label: 'Provider', sortable: false },
    { key: 'assignee_name', label: 'Assigned To', sortable: true },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'actions', label: 'Action', sortable: false },
]

// AC19: `is_overdue` is computed server-side (due date is in the past).
function rowClass(row) {
    return row.is_overdue ? 'vbc-row--overdue' : ''
}

function openCreate() {
    activityStore.openCreate(() => store.fetchAll())
}

function openActivity(row) {
    activityStore.openActivity(row, () => store.fetchAll())
}

// Client profile is a legacy Blade modal, loaded globally on every myaccount-layout page
// (resources/views/layouts/globalScript.blade.php) — reused as-is rather than rebuilt in Vue.
function openClientProfile(row) {
    window.getClientDetails?.(row.patient_id)
}

function onSearch(filterValues) {
    store.setFilters(filterValues)
}

function getFilteredData() {
    onSearch({ ...filtersStore.values })
}

function onClear() {
    store.resetFilters()
}

onMounted(() => store.fetchAll())
</script>

<style scoped>
/* Per-page selector is hidden on this bucket (:show-per-page="false"); without it the toolbar's
   only remaining child would flex to the left under `justify-content: space-between`. */
:deep(.ui-table__toolbar) {
    justify-content: flex-end;
}
</style>
