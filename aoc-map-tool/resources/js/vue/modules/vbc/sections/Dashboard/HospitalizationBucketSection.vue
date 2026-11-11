<template>
    <div>
        <HospitalizationTrackerModal />
        <HospitalizationBucketFilters @search="onSearch" @clear="onClear" />
        <p class="vbc-bucket-info">
            <u><b>Responsible Party:</b> ICM Team</u><br />
            <b>Description:</b> Active hospitalization trackers to be managed by the ICM Team<br />
            <b>Timeline:</b> 24 hours from admission date for open trackers. Expected discharge date
            higher than current date for trackers in progress.
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
                            title="Open Tracker"
                            @click="trackerStore.loadTrackerById(row.tracker_id)"
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
                            v-if="isNotesEligible(row.status)"
                            href="javascript:void(0)"
                            title="Notes"
                            @click="openNotesFor(row)"
                            ><i
                                class="fa fa-sticky-note-o ml-2"
                                aria-hidden="true"
                            ></i
                        ></a>
                    </div>
                </template>
            </ui-table>
        </div>
        <CreateNoteModal />
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { useHospitalization } from '@/modules/vbc/store/Dashboard/hospitalization.store.js'
import { useHospitalizationFiltersStore } from '@/modules/vbc/store/Dashboard/hospitalizationFilters.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { isNotesEligible } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import { UiTable } from '@angels-of-care/ui'
import HospitalizationBucketFilters from '../../components/HospitalizationBucketFilters.vue'
import SearchInput from '../../components/SearchInput.vue'
import CreateNoteModal from '../../components/HospitalizationTracker/CreateNoteModal.vue'
import HospitalizationTrackerModal from '../../components/HospitalizationTracker/HospitalizationTrackerModal.vue'

const store = useHospitalization()
const filtersStore = useHospitalizationFiltersStore()
const trackerStore = useHospitalizationTracker()

// AC11-13: standard AOC overdue row highlight (see `.vbc-row--overdue` in vbc.scss). `is_overdue`
// is already computed server-side per bucket's own rule (24h since admission / past expected discharge).
function rowClass(row) {
    return row.is_overdue ? 'vbc-row--overdue' : ''
}

function openNotesFor(row) {
    trackerStore.openCreateNoteForTracker(row.tracker_id)
}

// Client profile is a legacy Blade modal, loaded globally on every myaccount-layout page
// (resources/views/layouts/globalScript.blade.php) — reused as-is rather than rebuilt in Vue.
function openClientProfile(row) {
    window.getClientDetails?.(row.patient_id)
}

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'type', label: 'Hosp. Type', sortable: true },
    { key: 'level', label: 'Level', sortable: true },
    { key: 'tracker_id', label: 'Tracker ID', sortable: true },
    { key: 'admission_date', label: 'Admission Date', sortable: true },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'assignee_name', label: 'Assignee', sortable: false },
    { key: 'terminal_payer', label: 'Terminal Payer', sortable: false },
    { key: 'branch', label: 'Branch', sortable: false },
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

// A tracker created from this bucket's "+" button is still visible in the modal (wizardStep
// 'open') until the user closes it — refresh the list and filter options only then, not while
// the modal is still open.
watch(
    () => trackerStore.isModalOpen,
    (isOpen, wasOpen) => {
        if (wasOpen && !isOpen && trackerStore.justCreated) {
            trackerStore.justCreated = false
            store.fetchAll()
            filtersStore.fetchOptions()
        }
    }
)
</script>

<style scoped>
/* Per-page selector is hidden on this bucket (:show-per-page="false"); without it the toolbar's
   only remaining child would flex to the left under `justify-content: space-between`. */
:deep(.ui-table__toolbar) {
    justify-content: flex-end;
}
</style>
