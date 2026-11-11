<template>
    <div>
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">All Immunizations</h1>
                <a
                    title="Export"
                    href="javascript:void(0)"
                    :class="{ disabled: store.exporting }"
                    @click="!store.exporting && store.exportAll()"
                ><i class="fa fa-download"></i></a>
            </div>
        </div>

        <ImmunizationFilters
            @search="onSearch"
            @clear="onClear"
        />

        <div v-if="store.exportError">{{ store.exportError }}</div>

        <div v-if="store.error">{{ store.error }}</div>
        <div v-else>
            <ui-table
                :columns="columns"
                :rows="store.list"
                :loading="store.loading"
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

                <template #cell-actions>
                    <div style="white-space: nowrap">
                        <a href="javascript:void(0)"><i class="fa fa-calendar ml-2" aria-hidden="true"></i></a>
                    </div>
                </template>
            </ui-table>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useAllImmunization } from '@/modules/vbc/store/allImmunization.store.js'
import { useAllImmunizationFiltersStore } from '@/modules/vbc/store/immunizationFilters.store.js'
import { UiTable } from '@angels-of-care/ui';
import ImmunizationFilters from '@/modules/vbc/components/ImmunizationFilters.vue';
import SearchInput from '@/modules/vbc/components/SearchInput.vue';

const store = useAllImmunization()
const filtersStore = useAllImmunizationFiltersStore()

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'branch', label: 'Branch', sortable: true },
    { key: 'payer', label: 'Payer', sortable: true },
    { key: 'vaccine_name', label: 'Vaccine Name', sortable: true },
    { key: 'dose', label: 'Dose', sortable: true },
    { key: 'earliest_date', label: 'Earliest Date', sortable: true },
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'actions', label: 'Actions', sortable: false },
]

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
