import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: {
        getTrackerById: vi.fn(),
        createFollowUp: vi.fn(),
        updateFollowUpOutcome: vi.fn(),
    },
    CURRENT_USER: 'Anna Lopatnova',
}))

const CLIENT = {
    clientId: '12345-PDN',
    fullName: 'John Taylor',
    trackerId: '#12346',
    facilityName: 'First Provider of Dallas',
    admissionDateTime: '1/1/2026 12:00 AM',
    level: 'Level I/III',
}

describe('useFollowUp', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('openCreate resets state and registers the onSaved callback', () => {
        const store = useFollowUp()
        const onSaved = vi.fn()

        store.openCreate(onSaved)

        expect(store.modalOpen).toBe(true)
        expect(store.pendingClient).toBeNull()
        expect(store.followUp).toBeNull()
    })

    it('selectClientForCreate sets the pending client', () => {
        const store = useFollowUp()

        store.selectClientForCreate(CLIENT)

        expect(store.pendingClient).toEqual(CLIENT)
    })

    it('closeModal resets all state', () => {
        const store = useFollowUp()
        store.selectClientForCreate(CLIENT)
        store.openCreate(vi.fn())

        store.closeModal()

        expect(store.modalOpen).toBe(false)
        expect(store.pendingClient).toBeNull()
        expect(store.followUp).toBeNull()
    })

    it('submitCreate creates an Open follow-up and calls onSaved', async () => {
        const store = useFollowUp()
        const onSaved = vi.fn()
        store.openCreate(onSaved)
        store.selectClientForCreate(CLIENT)
        HospitalizationTrackerService.createFollowUp.mockResolvedValue({
            id: 'followup-10',
            status: 'Open',
            trackerId: '#12346',
        })

        const result = await store.submitCreate()

        expect(result).toBe(true)
        // The server derives client/facility/admission/level from the tracker — only its id is sent.
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).toHaveBeenCalledWith('#12346')
        expect(store.followUp).toEqual(
            expect.objectContaining({ id: 'followup-10', status: 'Open' }),
        )
        expect(store.pendingClient).toBeNull()
        expect(onSaved).toHaveBeenCalledOnce()
    })

    it('submitCreate does nothing without a pending client', async () => {
        const store = useFollowUp()

        const result = await store.submitCreate()

        expect(result).toBe(false)
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).not.toHaveBeenCalled()
    })

    it('submitOutcome Scheduled updates status and saves date/assignee', async () => {
        const store = useFollowUp()
        const onSaved = vi.fn()
        store.openCreate(onSaved)
        store.followUp = {
            id: 'followup-10',
            trackerId: '#12346',
            status: 'Open',
        }
        HospitalizationTrackerService.updateFollowUpOutcome.mockResolvedValue({
            id: 'followup-10',
            status: 'Scheduled',
            followUpDate: '3/01/2026 12:00 AM',
            assignedToId: 7,
        })

        await store.submitOutcome('Scheduled', {
            followUpDate: '3/01/2026 12:00 AM',
            assignedTo: 7,
        })

        expect(
            HospitalizationTrackerService.updateFollowUpOutcome,
        ).toHaveBeenCalledWith('followup-10', {
            outcome: 'SCHEDULED',
            scheduled_at: '3/01/2026 12:00 AM',
            assigned_to_id: 7,
        })
        expect(store.followUp.status).toBe('Scheduled')
        expect(onSaved).toHaveBeenCalledOnce()
    })

    it('submitOutcome Completed without a next follow-up sends next_follow_up_due false', async () => {
        const store = useFollowUp()
        store.followUp = {
            id: 'followup-10',
            trackerId: '#12346',
            status: 'Scheduled',
        }
        HospitalizationTrackerService.updateFollowUpOutcome.mockResolvedValue({
            id: 'followup-10',
            status: 'Completed',
        })

        await store.submitOutcome('Completed', {
            statusNote: 'a',
            followUpsUpdates: 'b',
            needs: 'c',
            additionalNotes: 'd',
            nextFollowUpDue: false,
        })

        expect(
            HospitalizationTrackerService.updateFollowUpOutcome,
        ).toHaveBeenCalledWith('followup-10', {
            outcome: 'COMPLETED',
            status_note: 'a',
            follow_ups_updates: 'b',
            needs: 'c',
            notes: 'd',
            next_follow_up_due: false,
            next_follow_up_due_date: undefined,
            next_assigned_to_id: undefined,
        })
        expect(store.followUp.status).toBe('Completed')
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).not.toHaveBeenCalled()
    })

    it('submitOutcome Completed with a next follow-up leaves the chaining to the server', async () => {
        const store = useFollowUp()
        store.followUp = {
            id: 'followup-10',
            trackerId: '#12346',
            status: 'Scheduled',
        }
        HospitalizationTrackerService.updateFollowUpOutcome.mockResolvedValue({
            id: 'followup-10',
            status: 'Completed',
        })

        await store.submitOutcome('Completed', {
            statusNote: 'a',
            followUpsUpdates: 'b',
            needs: 'c',
            nextFollowUpDue: true,
            nextFollowUpDueDate: '3/08/2026 12:00 AM',
            nextAssignedTo: 9,
        })

        expect(
            HospitalizationTrackerService.updateFollowUpOutcome,
        ).toHaveBeenCalledWith(
            'followup-10',
            expect.objectContaining({
                outcome: 'COMPLETED',
                next_follow_up_due: true,
                next_follow_up_due_date: '3/08/2026 12:00 AM',
                next_assigned_to_id: 9,
            }),
        )
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).not.toHaveBeenCalled()
    })

    it('submitOutcome Attempted with a next follow-up leaves the chaining to the server', async () => {
        const store = useFollowUp()
        store.followUp = {
            id: 'followup-10',
            trackerId: '#12346',
            status: 'Scheduled',
        }
        HospitalizationTrackerService.updateFollowUpOutcome.mockResolvedValue({
            id: 'followup-10',
            status: 'Attempted',
        })

        await store.submitOutcome('Attempted', {
            attemptNotes: 'notes',
            nextFollowUpDue: true,
            nextFollowUpDueDate: '3/08/2026 12:00 AM',
            nextAssignedTo: 9,
        })

        expect(
            HospitalizationTrackerService.updateFollowUpOutcome,
        ).toHaveBeenCalledWith('followup-10', {
            outcome: 'ATTEMPTED',
            notes: 'notes',
            next_follow_up_due: true,
            next_follow_up_due_date: '3/08/2026 12:00 AM',
            next_assigned_to_id: 9,
        })
        expect(store.followUp.status).toBe('Attempted')
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).not.toHaveBeenCalled()
    })

    it('submitOutcome Canceled finalizes without a next follow-up', async () => {
        const store = useFollowUp()
        store.followUp = {
            id: 'followup-10',
            trackerId: '#12346',
            status: 'Open',
        }
        HospitalizationTrackerService.updateFollowUpOutcome.mockResolvedValue({
            id: 'followup-10',
            status: 'Canceled',
        })

        await store.submitOutcome('Canceled', {})

        expect(
            HospitalizationTrackerService.updateFollowUpOutcome,
        ).toHaveBeenCalledWith('followup-10', { outcome: 'CANCELED' })
        expect(store.followUp.status).toBe('Canceled')
        expect(
            HospitalizationTrackerService.createFollowUp,
        ).not.toHaveBeenCalled()
    })

    it('openExisting resolves the matching follow-up from the fetched tracker', async () => {
        const store = useFollowUp()
        HospitalizationTrackerService.getTrackerById.mockResolvedValue({
            trackerId: '#12346',
            followUps: [
                { id: 'followup-1', status: 'Open' },
                { id: 'followup-2', status: 'Scheduled' },
            ],
        })

        await store.openExisting({
            trackerId: '#12346',
            followUpId: 'followup-2',
        })

        expect(store.modalOpen).toBe(true)
        expect(store.followUp).toEqual(
            expect.objectContaining({ id: 'followup-2', status: 'Scheduled' }),
        )
    })
})
