import { defineStore } from 'pinia'
import { ref } from 'vue'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

// `outcome` on the wire, as UpdateFollowupOutcomeRequest accepts it — see
// HospitalizationTrackerFollowupStatusEnum.
const OUTCOME_TO_ENUM = {
    Scheduled: 'SCHEDULED',
    Completed: 'COMPLETED',
    Attempted: 'ATTEMPTED',
    Canceled: 'CANCELED',
}

// Follow-up modal lifecycle (Hospitalization Follow-ups story). Unlike
// every other tab modal in this codebase (Contacts/Diagnosis/Sick
// Plan/Education, which close on every successful submit), this modal
// stays open across outcome transitions (New -> Open -> Scheduled ->
// Completed, etc. — design.md Decision 1) — only `closeModal` actually
// hides it. `onSaved` is an optional callback registered by whichever
// screen opened the modal (the Communications bucket or a tracker's
// Follow-up tab), invoked after every successful mutation so that
// screen's list reflects the change (design.md Decisions 4/5).
export const useFollowUp = defineStore('followUp', () => {
    const modalOpen = ref(false)
    const pendingClient = ref(null) // search selection, before the first save
    const followUp = ref(null) // the persisted record, once it exists
    const onSaved = ref(null)

    function openCreate(onSavedCallback) {
        pendingClient.value = null
        followUp.value = null
        onSaved.value = onSavedCallback ?? null
        modalOpen.value = true
    }

    async function openExisting({ trackerId, followUpId }, onSavedCallback) {
        const tracker =
            await HospitalizationTrackerService.getTrackerById(trackerId)
        const found = tracker?.followUps?.find((f) => f.id === followUpId)
        pendingClient.value = null
        followUp.value = found ? { ...found, trackerId } : null
        onSaved.value = onSavedCallback ?? null
        modalOpen.value = true
    }

    function selectClientForCreate(client) {
        pendingClient.value = client
    }

    function closeModal() {
        modalOpen.value = false
        pendingClient.value = null
        followUp.value = null
        onSaved.value = null
    }

    async function submitCreate() {
        const client = pendingClient.value
        if (!client) return false

        const created = await HospitalizationTrackerService.createFollowUp(
            client.trackerId,
        )
        followUp.value = created
        pendingClient.value = null
        onSaved.value?.()
        return true
    }

    // The server derives Completion/Attempt Date+By itself and, when `nextFollowUpDue` is set, chains
    // the next Scheduled follow-up in the same transaction (AC13/AC17) — this only has to forward what
    // the user entered.
    async function submitOutcome(outcome, fields) {
        const current = followUp.value
        if (!current) return

        let payload
        if (outcome === 'Scheduled') {
            payload = {
                scheduled_at: fields.followUpDate,
                assigned_to_id: fields.assignedTo,
            }
        } else if (outcome === 'Completed') {
            payload = {
                status_note: fields.statusNote,
                follow_ups_updates: fields.followUpsUpdates,
                needs: fields.needs,
                notes: fields.additionalNotes,
                next_follow_up_due: fields.nextFollowUpDue,
                next_follow_up_due_date: fields.nextFollowUpDueDate,
                next_assigned_to_id: fields.nextAssignedTo,
            }
        } else if (outcome === 'Attempted') {
            payload = {
                notes: fields.attemptNotes,
                next_follow_up_due: fields.nextFollowUpDue,
                next_follow_up_due_date: fields.nextFollowUpDueDate,
                next_assigned_to_id: fields.nextAssignedTo,
            }
        } else if (outcome === 'Canceled') {
            payload = {}
        } else {
            return
        }

        payload.outcome = OUTCOME_TO_ENUM[outcome]

        followUp.value = await HospitalizationTrackerService.updateFollowUpOutcome(
            current.id,
            payload,
        )

        onSaved.value?.()
    }

    return {
        modalOpen,
        pendingClient,
        followUp,
        openCreate,
        openExisting,
        selectClientForCreate,
        closeModal,
        submitCreate,
        submitOutcome,
    }
})
