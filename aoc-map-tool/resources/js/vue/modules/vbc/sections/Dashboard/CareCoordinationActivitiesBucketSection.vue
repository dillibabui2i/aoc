<template>
    <div>
        <CareCoordinationFilters @search="onSearch" @clear="onClear" />
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
                    <SearchInput v-model="filtersStore.values.search" @search="getFilteredData" />
                </template>

                <template #cell-actions="{ row }">
                    <div style="white-space: nowrap">
                        <a
                            href="javascript:void(0)"
                            title="Open Care Coordination"
                            @click="store.openActivity(row)"
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
                    </div>
                </template>
            </ui-table>
        </div>
        <CareCoordinationModal />
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/careCoordinationActivities.store.js'
import { useCareCoordinationFiltersStore } from '@/modules/vbc/store/Dashboard/careCoordinationFilters.store.js'
import { UiTable } from '@angels-of-care/ui'
import CareCoordinationFilters from '@/modules/vbc/components/CareCoordinationFilters.vue'
import CareCoordinationModal from '@/modules/vbc/components/CareCoordinationActivity/CareCoordinationModal.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'

const store = useCareCoordinationActivities()
const filtersStore = useCareCoordinationFiltersStore()

const columns = [
    { key: 'created_at', label: 'Created Date', sortable: true },
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'hospital_name', label: 'Hospital', sortable: false },
    { key: 'coordination_type', label: 'Type', sortable: true },
    { key: 'provider', label: 'Provider', sortable: false },
    { key: 'assignee_name', label: 'Assigned To', sortable: false },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'actions', label: 'Action', sortable: false },
]

// AC16-17: standard AOC overdue row highlight (see `.vbc-row--overdue` in vbc.scss). `is_overdue`
// is already computed server-side (due_at is in the past).
function rowClass(row) {
    return row.is_overdue ? 'vbc-row--overdue' : ''
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
