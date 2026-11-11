<template>
    <div>
        <CommunicationFilters @search="onSearch" @clear="onClear" />
        <p class="vbc-bucket-info">
            <u><b>Responsible Party:</b> ICM Team</u><br />
            <b>Description:</b> Upcoming follow-ups for the next seven days<br />
            <b>Timeline:</b> Overdue follow-ups (due date &gt; 2 days from current date)
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

                <template #cell-is_hospitalized="{ value }">
                    {{ value ? 'Yes' : 'No' }}
                </template>

                <template #cell-actions="{ row }">
                    <div style="white-space: nowrap">
                        <a
                            href="javascript:void(0)"
                            title="Open Follow-up"
                            @click="openFollowUp(row)"
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

        <FollowUpModal />
    </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useCommunication } from '@/modules/vbc/store/Dashboard/communication.store.js'
import { useCommunicationsFiltersStore } from '@/modules/vbc/store/Dashboard/communicationFilters.store.js'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'
import { UiTable } from '@angels-of-care/ui'
import CommunicationFilters from '@/modules/vbc/components/CommunicationFilters.vue'
import FollowUpModal from '@/modules/vbc/components/FollowUp/FollowUpModal.vue'
import SearchInput from '@/modules/vbc/components/SearchInput.vue'

const store = useCommunication()
const filtersStore = useCommunicationsFiltersStore()
const followUpStore = useFollowUp()

function openFollowUp(row) {
    followUpStore.openExisting(
        {
            trackerId: row.hospitalization_tracker_id,
            followUpId: row.id,
        },
        () => store.fetchAll(),
    )
}

const columns = [
    { key: 'patient_name', label: 'Patient Name', sortable: true },
    { key: 'patient_account', label: 'Patient ID', sortable: true },
    { key: 'hospitalization_tracker_id', label: 'Tracker ID', sortable: false },
    { key: 'hospitalization_level', label: 'Level', sortable: false },
    { key: 'is_hospitalized', label: 'Hospitalized?', sortable: false },
    { key: 'status', label: 'Status', sortable: false },
    { key: 'facility_name', label: 'Facility', sortable: false },
    { key: 'assignee_name', label: 'Assignee', sortable: false },
    { key: 'followup_date', label: 'Due Date', sortable: true },
    { key: 'actions', label: 'Action', sortable: false },
]

// AC22: standard AOC overdue row highlight (see `.vbc-row--overdue` in vbc.scss). `is_overdue`
// is already computed server-side (more than 2 days past the scheduled follow-up date).
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
