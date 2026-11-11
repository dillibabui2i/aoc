<template>
    <div>
        <ImmunizationBucketFilters @search="onSearch" @clear="onClear" />
        <p class="vbc-bucket-info">
            <u><b>Responsible Party:</b> Immunization Team</u><br />
            <b>Description:</b> Vaccine doses that still require action<br />
            <b>Timeline:</b> Overdue doses are highlighted
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

                <template #cell-actions>
                    <!-- Opening the vaccine dose is delivered by PD-4339. -->
                    <div style="white-space: nowrap">
                        <a href="javascript:void(0)" title="Open Vaccine Dose"
                            ><i class="fa fa-calendar ml-2" aria-hidden="true"></i
                        ></a>
                    </div>
                </template>
            </ui-table>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useImmunizationBucket } from '@/modules/vbc/store/Dashboard/immunizationBucket.store.js'
import { useImmunizationBucketFiltersStore } from '@/modules/vbc/store/Dashboard/immunizationBucketFilters.store.js'
import ImmunizationBucketFilters from '@/modules/vbc/components/ImmunizationBucketFilters.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'

const store = useImmunizationBucket()
const filtersStore = useImmunizationBucketFiltersStore()

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'branch', label: 'Patient Branch', sortable: true },
    { key: 'payer', label: 'Patient Payer', sortable: true },
    { key: 'vaccine_name', label: 'Vaccine Name', sortable: true },
    { key: 'earliest_date', label: 'Vaccine Dose Earliest Date', sortable: true },
    { key: 'due_date', label: 'Vaccine Dose Due Date', sortable: true },
    { key: 'assignee_name', label: 'Assigned To', sortable: true },
    { key: 'outcome', label: 'Outcome', sortable: true },
    { key: 'actions', label: 'Action', sortable: false },
]

// Standard AOC overdue row highlight (see `.vbc-row--overdue` in vbc.scss); `is_overdue` is computed server-side.
function rowClass(row) {
    return row.is_overdue ? 'vbc-row--overdue' : ''
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
