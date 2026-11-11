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

        <EdCareCoordinationModal v-if="hostsCareCoordinationModal" />
    </div>
</template>

<script setup>
// PD-3969: Care Coordination tab on the ED Visit Tracker modal — ED copy of
// HospitalizationTracker/CareCoordinationTab.vue. Shows every Care Coordination activity tied to this
// ED tracker's encounter, each opened in the ED Care Coordination modal (editable while the tracker is In
// Progress, read-only otherwise). The tracker is implied — no patient picker, same as the IP tab.
import { ref, computed, watch } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'
import EdCareCoordinationModal from './EdCareCoordinationModal.vue'

const trackerStore = useEdVisitTracker()
const careCoordinationStore = useEdCareCoordinationActivities()

// The modal is bound to one page-wide store. When the Triage Care Coordination bucket is on the page it
// already renders it — a second copy here would open on top of the first.
const hostsCareCoordinationModal = !document.querySelector(
    '#vbc-bucket-triage-care-coordination',
)

const items = ref([])

const columns = [
    { key: 'due_date', label: 'Due Date', sortable: true },
    { key: 'coordination_type', label: 'Type', sortable: true },
    { key: 'provider', label: 'Provider', sortable: true },
    { key: 'assignee_name', label: 'Assigned To', sortable: true },
    { key: 'is_completed', label: 'Completed?', sortable: true },
    { key: 'action', label: 'Action' },
]

// The whole list is fetched in one page (per_page: 100 below), so sorting happens client-side.
// `due_date` is a formatted 'm/d/Y h:i A' string (config('vbc.date_time_format')), so it is parsed
// against that exact known format rather than handed to `Date`.
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

    const res = await edCareCoordinationActivitiesService.getAll({
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
