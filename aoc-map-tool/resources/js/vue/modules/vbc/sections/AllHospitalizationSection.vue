<template>
    <div>
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">All Hospitalizations</h1>
                <a
                    title="Export"
                    href="javascript:void(0)"
                    :class="{ disabled: store.exporting }"
                    @click="!store.exporting && store.exportAll()"
                ><i class="fa fa-download"></i></a>
                <a
                    title="New Hospitalization Record"
                    href="javascript:void(0)"
                    class="ml-2"
                    @click="trackerStore.openCreateModal()"
                ><i class="fa fa-plus-circle"></i></a>
            </div>
        </div>

        <HospitalizationTrackerModal />

        <HospitalizationFilters
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

                <template #cell-actions="{ row }">
                    <div style="white-space: nowrap">
                        <a
                            title="Open Hospitalization Record"
                            href="javascript:void(0)"
                            @click="trackerStore.loadTrackerById(row.tracker_id)"
                        ><i class="fa fa-calendar ml-2" aria-hidden="true"></i></a>
                        <a
                            title="View Client Profile"
                            href="javascript:void(0)"
                            @click="openClientProfile(row)"
                        ><i class="fa fa-user ml-2" aria-hidden="true"></i></a>
                        <a
                            v-if="isNotesEligible(row.status)"
                            title="Notes"
                            href="javascript:void(0)"
                            @click="openNotesFor(row)"
                        ><i class="fa fa-sticky-note-o ml-2" aria-hidden="true"></i></a>
                    </div>
                </template>
            </ui-table>
        </div>

        <CreateNoteModal />
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useAllHospitalization } from '@/modules/vbc/store/allHospitalization.store.js'
import { useAllHospitalizationFiltersStore } from '@/modules/vbc/store/filters.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { isNotesEligible } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import { UiTable } from '@angels-of-care/ui';
import HospitalizationFilters from '@/modules/vbc/components/HospitalizationFilters.vue';
import SearchInput from '@/modules/vbc/components/SearchInput.vue';
import HospitalizationTrackerModal from '@/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue';
import CreateNoteModal from '@/modules/vbc/components/HospitalizationTracker/CreateNoteModal.vue';

const store = useAllHospitalization()
const filtersStore = useAllHospitalizationFiltersStore()
const trackerStore = useHospitalizationTracker()

function openNotesFor(row) {
    trackerStore.openCreateNoteForTracker(row.tracker_id)
}

// Client profile is a legacy Blade modal, loaded globally on every myaccount-layout page
// (resources/views/layouts/globalScript.blade.php) — reused as-is rather than rebuilt in Vue.
function openClientProfile(row) {
    window.getClientDetails?.(row.patient_id)
}

// A tracker created or edited in the modal changes what this table should show,
// so reload it when the modal closes rather than making the user refresh. Kept
// here, in the component that owns the table, so nothing fetches this list on
// screens where it isn't rendered.
watch(
    () => trackerStore.isModalOpen,
    (open, wasOpen) => {
        if (!wasOpen || open) return

        store.fetchAll()
        // The filter bar's Assignee and Facility Name lists are derived from the
        // trackers that exist — a record just created or reassigned changes both,
        // so its own assignee would be missing from the filter that should find it.
        filtersStore.fetchOptions()
    },
)

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'tracker_id', label: 'Tracker ID', sortable: true },
    { key: 'level', label: 'Level', sortable: true },
    { key: 'type', label: 'Admission Type', sortable: true },
    { key: 'admission_date', label: 'Admission Date', sortable: true },
    { key: 'facility_name', label: 'Facility Name', sortable: false },
    { key: 'assignee_name', label: 'Assignee', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'discharge_date', label: 'Discharge Date', sortable: true },
    { key: 'terminal_payer', label: 'Terminal Payer', sortable: false },
    { key: 'branch', label: 'Branch', sortable: false },
    { key: 'last_follow_up', label: 'Last Follow-up', sortable: true },
    { key: 'actions', label: 'Action', sortable: false },
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

<style scoped>

</style>
