<template>
    <div class="follow-up-tab">
        <ui-table
            :columns="columns"
            :rows="followUps"
            :total="followUps.length"
            :page="1"
            :per-page="followUps.length || 1"
        >
            <template #cell-action="{ row }">
                <a href="javascript:void(0)" @click="openFollowUp(row)">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
            </template>
        </ui-table>

        <FollowUpModal />
    </div>
</template>

<script setup>
// Follow-up tab (AC37-AC41): shows every follow-up tied to this tracker,
// including Completed/Attempted ones (unlike the Communications bucket),
// sorted newest-created-first. Opening a follow-up always re-fetches via
// `followUpStore.openExisting` (design.md Decision 4 of that story);
// `hospitalizationTrackerStore.refreshTracker` is passed as the `onSaved`
// callback so this table updates immediately after any submit
// (design.md Decision 5).
import { computed } from 'vue'
import { UiTable } from '@angels-of-care/ui'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'
import FollowUpModal from '../FollowUp/FollowUpModal.vue'

const trackerStore = useHospitalizationTracker()
const followUpStore = useFollowUp()

const followUps = computed(() =>
    [...(trackerStore.tracker?.followUps ?? [])].sort(
        (a, b) => (b.createdAt ?? 0) - (a.createdAt ?? 0),
    ),
)

const columns = [
    { key: 'followUpDate', label: 'Follow-up Date' },
    { key: 'assignedTo', label: 'Assigned To' },
    { key: 'status', label: 'Status' },
    { key: 'action', label: 'Actions' },
]

function openFollowUp(row) {
    followUpStore.openExisting(
        { trackerId: trackerStore.tracker.tracker_id, followUpId: row.id },
        () => trackerStore.refreshTracker(),
    )
}
</script>
