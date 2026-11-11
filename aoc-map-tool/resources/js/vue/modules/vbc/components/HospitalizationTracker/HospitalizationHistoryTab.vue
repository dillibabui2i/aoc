<template>
    <div class="hospitalization-history-tab">
        <ui-table
            :columns="columns"
            :rows="sortedHistory"
            :total="history.length"
            :page="1"
            :per-page="history.length || 1"
            :show-per-page="false"
            :sort-by="sortBy"
            :sort-dir="sortDir"
            @update:sort-by="sortBy = $event"
            @update:sort-dir="sortDir = $event"
        >
            <template #cell-discharged_at="{ row }">
                {{ row.discharged_at ?? '-' }}
            </template>
            <template #cell-action="{ row }">
                <a
                    v-if="row.encounter_type === 'IP'"
                    href="javascript:void(0)"
                    @click="store.loadTrackerById(row.tracker_id)"
                >
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>
    </div>
</template>

<script setup>
// Hospitalization History tab: the hospitalizations the patient was transferred from, nearest first. Unlike
// the other tabs this list is not part of the tracker payload, so it is fetched through the store
// (`loadTransferHistory`) when the tab opens and again whenever a transfer swaps the tracker in place (which
// does not remount the tab). The whole list is loaded at once, so sorting is client-side and the per-page
// control is hidden — same as ContactsTab.vue/DiagnosisTab.vue. The calendar icon opens that
// hospitalization's own record (read-only, it is Closed). Only IP rows get it: `loadTrackerById` opens
// Hospitalization trackers only, so an ED Visit row (the Event column already labels one correctly, should
// the history ever include them) would need the ED viewer wired in first — edVisitTracker.store.js.
import { computed, onMounted, ref, watch } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const store = useHospitalizationTracker()

const history = computed(() => store.transferHistory)

onMounted(store.loadTransferHistory)
watch(() => store.tracker?.tracker_id, store.loadTransferHistory)

const columns = [
    { key: 'date', label: 'Date', sortable: true },
    { key: 'event', label: 'Event', sortable: true },
    { key: 'facility_name', label: 'Facility Name', sortable: true },
    { key: 'discharged_at', label: 'Discharged at', sortable: true },
    { key: 'action', label: 'Action' },
]

const sortBy = ref(null)
const sortDir = ref('asc')

// The date columns arrive as `m/d/Y`, which does not sort alphabetically across years — compared as
// `Ymd` instead (an empty one, e.g. not discharged yet, sorts first).
const DATE_COLUMNS = ['date', 'discharged_at']

function sortValue(row) {
    const value = row[sortBy.value] ?? ''

    if (DATE_COLUMNS.includes(sortBy.value)) {
        const [month, day, year] = value.split('/')

        return year ? `${year}${month}${day}` : ''
    }

    return value.toString().toLowerCase()
}

const sortedHistory = computed(() => {
    if (!sortBy.value) return history.value

    const dir = sortDir.value === 'desc' ? -1 : 1

    return [...history.value].sort((a, b) => {
        const valueA = sortValue(a)
        const valueB = sortValue(b)

        if (valueA < valueB) return -1 * dir
        if (valueA > valueB) return 1 * dir
        return 0
    })
})
</script>
