<template>
    <div>
        <HospitalizationTrackerModal v-if="hostsTrackerModals" />
        <EdVisitTrackerModal />
        <EdVisitsBucketFilters @search="onSearch" @clear="onClear" />
        <p class="vbc-bucket-info">
            <u><b>Responsible Party:</b> Triage Team</u><br />
            <b>Description:</b> Active ED Visit trackers to be managed by the
            Triage Team<br />
            <b>Timeline:</b> 24 hours from admission date.
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
                            title="Open Tracker"
                            @click="
                                edTrackerStore.loadTrackerById(row.tracker_id)
                            "
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
                            v-if="isEdNotesEligible(row.status)"
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
    </div>
</template>

<script setup>
import { onMounted, watch } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useEdVisits } from '@/modules/vbc/store/Dashboard/edVisits.store.js'
import { useEdVisitsFiltersStore } from '@/modules/vbc/store/Dashboard/edVisitsFilters.store.js'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { isEdNotesEligible } from '@/modules/vbc/services/edVisitTracker.service.js'
import EdVisitsBucketFilters from '@/modules/vbc/components/EdVisitsBucketFilters.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'
import HospitalizationTrackerModal from '@/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue'
import EdVisitTrackerModal from '@/modules/vbc/components/EdVisitTracker/EdVisitTrackerModal.vue'

const store = useEdVisits()
const filtersStore = useEdVisitsFiltersStore()
const edTrackerStore = useEdVisitTracker()

// Both modals are bound to the one page-wide `useHospitalizationTracker()` store. The ICM dashboard's
// Hospitalization bucket (#vbc-bucket-hospitalization) already renders them, and when the user holds
// both permissions it is on this same page — a second copy here would open on top of the first. So
// this bucket only hosts them when that one isn't on the page; opening them from here works either way.
const hostsTrackerModals = !document.querySelector(
    '#vbc-bucket-hospitalization',
)

// AC18: `is_overdue` is computed server-side (admitted more than 24 hours ago).
function rowClass(row) {
    return row.is_overdue ? 'vbc-row--overdue' : ''
}

function openNotesFor(row) {
    edTrackerStore.openCreateNoteForTracker(row.tracker_id)
}

// Client profile is a legacy Blade modal, loaded globally on every myaccount-layout page
// (resources/views/layouts/globalScript.blade.php) — reused as-is rather than rebuilt in Vue.
function openClientProfile(row) {
    window.getClientDetails?.(row.patient_id)
}

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'level', label: 'Patient Level', sortable: true },
    { key: 'tracker_id', label: 'Tracker ID', sortable: true },
    { key: 'admission_date', label: 'Admission Date', sortable: true },
    { key: 'follow_up_status', label: 'Follow-up Status', sortable: false },
    { key: 'assignee_name', label: 'Assignee', sortable: true },
    { key: 'terminal_payer', label: 'Terminal Payer', sortable: false },
    { key: 'branch', label: 'Branch', sortable: true },
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

// A tracker created from this bucket's "+" button (PD-3983) is still visible in the modal
// (wizardStep 'open') until the user closes it — refresh the list only then, same reasoning as
// HospitalizationBucketSection.vue's equivalent watcher.
watch(
    () => edTrackerStore.isModalOpen,
    (isOpen, wasOpen) => {
        if (wasOpen && !isOpen && edTrackerStore.justCreated) {
            edTrackerStore.justCreated = false
            store.fetchAll()
        }
    },
)
</script>

<style scoped>
/* Per-page selector is hidden on this bucket (:show-per-page="false"); without it the toolbar's
   only remaining child would flex to the left under `justify-content: space-between`. */
:deep(.ui-table__toolbar) {
    justify-content: flex-end;
}
</style>
