<template>
    <div class="care-coordination-tab">
        <ui-table
            :columns="columns"
            :rows="sortedItems"
            :total="items.length"
            :page="1"
            :per-page="items.length || 1"
            :show-per-page="false"
            :sort-by="sortBy"
            :sort-dir="sortDir"
            @update:sort-by="sortBy = $event"
            @update:sort-dir="sortDir = $event"
        >
            <template #cell-is_completed="{ row }">
                {{ row.is_completed ? 'Yes' : 'No' }}
            </template>
            <template #cell-action="{ row }">
                <a href="javascript:void(0)" @click="openItem(row)">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <CareCoordinationModal />
    </div>
</template>

<script setup>
// Care Coordination tab on the Hospitalization Tracker modal: shows every Care Coordination activity
// tied to this tracker's encounter (care_coordinations links via encounter_id, not tracker_id — see the
// architecture decision on PD-3494), each editable via the same modal the ICM dashboard bucket uses.
// This is a separate, tracker-scoped list — not the dashboard bucket's own paginated one — so `fetchAll()`
// on the shared store (see careCoordinationActivities.store.js) won't refresh it; refetch() is passed as
// the `onSaved` callback instead.
import { ref, computed, watch } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { useCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/careCoordinationActivities.store.js'
import { careCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js'
import CareCoordinationModal from '../CareCoordinationActivity/CareCoordinationModal.vue'

const trackerStore = useHospitalizationTracker()
const careCoordinationStore = useCareCoordinationActivities()

const items = ref([])

const columns = [
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'coordination_type', label: 'Type', sortable: true },
    { key: 'provider', label: 'Provider', sortable: true },
    { key: 'assignee_name', label: 'Assigned To', sortable: true },
    { key: 'is_completed', label: 'Completed?', sortable: true },
    { key: 'action', label: 'Action' },
]

// The whole list is fetched in one page (per_page: 100 below), so sorting happens client-side against
// that already-loaded array rather than round-tripping to the backend for it — `due_date` is a
// formatted 'm/d/Y h:i A' string (config('vbc.date_time_format')), not sortable lexicographically, so it
// gets parsed against that exact known format rather than handed to `Date` (see the "avoid implicit
// date parsing" convention this codebase already follows on the PHP side with Carbon).
const sortBy = ref('due_date')
const sortDir = ref('asc')

function parseDueDate(value) {
    const match = /^(\d{2})\/(\d{2})\/(\d{4}) (\d{2}):(\d{2}) (AM|PM)$/.exec(value ?? '')
    if (!match) return null

    const [, month, day, year, hour, minute, ampm] = match
    const hour24 = (Number(hour) % 12) + (ampm === 'PM' ? 12 : 0)

    return new Date(Number(year), Number(month) - 1, Number(day), hour24, Number(minute)).getTime()
}

function sortValue(row, key) {
    if (key === 'due_date') return parseDueDate(row.due_date) ?? -Infinity
    if (key === 'is_completed') return row.is_completed ? 1 : 0

    return (row[key] ?? '').toString().toLowerCase()
}

const sortedItems = computed(() => {
    const dir = sortDir.value === 'desc' ? -1 : 1

    return [...items.value].sort((a, b) => {
        const valueA = sortValue(a, sortBy.value)
        const valueB = sortValue(b, sortBy.value)

        if (valueA < valueB) return -1 * dir
        if (valueA > valueB) return 1 * dir
        return 0
    })
})

async function refetch() {
    const trackerId = trackerStore.tracker?.tracker_id
    if (!trackerId) {
        items.value = []
        return
    }

    const res = await careCoordinationActivitiesService.getAll({
        hospitalization_tracker_id: trackerId,
        per_page: 100,
        sort_by: 'due_date',
        sort_dir: 'asc',
    })
    items.value = res.data
}

watch(() => trackerStore.tracker?.tracker_id, refetch, { immediate: true })

function openItem(row) {
    careCoordinationStore.openActivity(row, refetch)
}
</script>
