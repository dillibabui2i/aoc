import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'

// PD-4069: the Outcome workflow's store actions. The rest of this store (client search, creation,
// Hospitalization Info save) predates this ticket and is intentionally left uncovered here, same scope
// boundary as the rest of the suite.
vi.mock('@/modules/vbc/services/edVisitTracker.service.js', () => ({
    EdVisitTrackerService: {
        searchClients: vi.fn(),
        getClient: vi.fn(),
        getAssignees: vi.fn(),
        createTracker: vi.fn(),
        getTrackerById: vi.fn(),
        saveField: vi.fn(),
        saveInfo: vi.fn(),
        updateOutcome: vi.fn(),
        getTrackerNotes: vi.fn(),
        createNote: vi.fn(),
        createContact: vi.fn(),
        updateContact: vi.fn(),
        getSickPlan: vi.fn(),
        updateSickPlanItem: vi.fn(),
    },
}))

const TRACKER = {
    tracker_id: 42,
    status: 'In Progress',
    assigned_to_id: 7,
    sent_to_review_by_id: null,
    encounter_type: 'ED',
}

describe('useEdVisitTracker outcome workflow', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('submitReadyForReview sends the backend enum outcome and reassigns the tracker', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        const updated = {
            ...TRACKER,
            status: 'Ready for Review',
            assigned_to_id: 9,
        }
        EdVisitTrackerService.updateOutcome.mockResolvedValue(updated)
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])

        await store.submitReadyForReview({
            reviewer: 9,
            notes: 'Please review.',
        })

        expect(EdVisitTrackerService.updateOutcome).toHaveBeenCalledWith(42, {
            outcome: 'READY_FOR_REVIEW',
            assigneeId: 9,
            notes: 'Please review.',
        })
        expect(store.tracker).toEqual(updated)
        expect(store.outcomeError).toBeNull()
    })

    it('submitReadyForNextSteps sends the backend enum outcome', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER, status: 'Ready for Review' }
        EdVisitTrackerService.updateOutcome.mockResolvedValue({
            ...TRACKER,
            status: 'Ready for Next Steps',
        })
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])

        await store.submitReadyForNextSteps({ nextStepsBy: 7, notes: '' })

        expect(EdVisitTrackerService.updateOutcome).toHaveBeenCalledWith(42, {
            outcome: 'READY_FOR_NEXT_STEPS',
            assigneeId: 7,
            notes: '',
        })
    })

    it('submitCompleted sends the backend enum outcome', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER, status: 'Ready for Next Steps' }
        EdVisitTrackerService.updateOutcome.mockResolvedValue({
            ...TRACKER,
            status: 'Completed',
        })
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])

        await store.submitCompleted({ completedBy: 7, notes: '' })

        expect(EdVisitTrackerService.updateOutcome).toHaveBeenCalledWith(42, {
            outcome: 'COMPLETED',
            assigneeId: 7,
            notes: '',
        })
    })

    it('submitClosed sends the reason and notes, not an assignee', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        EdVisitTrackerService.updateOutcome.mockResolvedValue({
            ...TRACKER,
            status: 'Closed',
        })
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])

        await store.submitClosed({
            reason: 'HOSPITALIZATION',
            notes: 'Converted.',
        })

        expect(EdVisitTrackerService.updateOutcome).toHaveBeenCalledWith(42, {
            outcome: 'CLOSED',
            reason: 'HOSPITALIZATION',
            notes: 'Converted.',
        })
    })

    it('a failed transition surfaces the server message and leaves the tracker untouched', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        const error = {
            response: {
                data: {
                    message: 'Cannot move an In Progress tracker to Completed.',
                },
            },
        }
        EdVisitTrackerService.updateOutcome.mockRejectedValue(error)

        await store.submitCompleted({ completedBy: 7, notes: '' })

        expect(store.outcomeError).toBe(
            'Cannot move an In Progress tracker to Completed.',
        )
        expect(store.tracker).toEqual(TRACKER)
        expect(EdVisitTrackerService.getTrackerNotes).not.toHaveBeenCalled()
    })

    it('a transition blocked by empty required fields exposes their keys, cleared on the next attempt (PD-5800)', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        EdVisitTrackerService.updateOutcome.mockRejectedValueOnce({
            response: {
                data: {
                    message: 'Complete the following fields: Date Report filed.',
                    missing_fields: ['ed_visit_report_filed_at'],
                },
            },
        })

        await store.submitCompleted({ completedBy: 7, notes: '' })
        expect(store.outcomeMissingFields).toEqual(['ed_visit_report_filed_at'])

        EdVisitTrackerService.updateOutcome.mockResolvedValueOnce({ ...TRACKER })
        await store.submitCompleted({ completedBy: 7, notes: '' })
        expect(store.outcomeMissingFields).toEqual([])
    })

    it('loadTrackerById reloads the notes panel for AC15 persistence after a refresh', async () => {
        const store = useEdVisitTracker()
        EdVisitTrackerService.getTrackerById.mockResolvedValue(TRACKER)
        const notes = [
            {
                id: 1,
                notes: 'Ready for Review by Jane Doe at 1/1/2026 10:00 AM',
            },
        ]
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue(notes)

        await store.loadTrackerById(42)

        expect(store.tracker).toEqual(TRACKER)
        expect(store.trackerNotes).toEqual(notes)
    })

    it('loadTrackerById loads the assignees so the Assigned To select has options (PD-5636)', async () => {
        const store = useEdVisitTracker()
        const assignees = [{ value: 7, label: 'Jane Doe' }]
        EdVisitTrackerService.getTrackerById.mockResolvedValue(TRACKER)
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])
        EdVisitTrackerService.getAssignees.mockResolvedValue(assignees)

        await store.loadTrackerById(42)

        expect(EdVisitTrackerService.getAssignees).toHaveBeenCalledTimes(1)
        expect(store.assignees).toEqual(assignees)
    })

    it('openExistingTracker loads the assignees for an ED tracker (PD-5636)', async () => {
        const store = useEdVisitTracker()
        const assignees = [{ value: 7, label: 'Jane Doe' }]
        store.activeTrackerConflict = { tracker_id: 42 }
        EdVisitTrackerService.getTrackerById.mockResolvedValue(TRACKER)
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])
        EdVisitTrackerService.getAssignees.mockResolvedValue(assignees)

        await store.openExistingTracker()

        expect(store.assignees).toEqual(assignees)
    })

    it('does not load assignees when the fetched tracker is not an ED one (PD-5636)', async () => {
        const store = useEdVisitTracker()
        EdVisitTrackerService.getTrackerById.mockResolvedValue({
            ...TRACKER,
            encounter_type: 'IP',
        })

        await expect(store.loadTrackerById(42)).rejects.toThrow(
            'Tracker 42 is not an ED Visit tracker (encounter_type: IP).',
        )

        expect(EdVisitTrackerService.getAssignees).not.toHaveBeenCalled()
    })

    it('still opens the tracker when the assignees request fails (PD-5636)', async () => {
        const store = useEdVisitTracker()
        EdVisitTrackerService.getTrackerById.mockResolvedValue(TRACKER)
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue([])
        EdVisitTrackerService.getAssignees.mockRejectedValue(new Error('boom'))

        await store.loadTrackerById(42)

        expect(store.isModalOpen).toBe(true)
        expect(store.tracker).toEqual(TRACKER)
        EdVisitTrackerService.getAssignees.mockReset()
    })
})

describe('useEdVisitTracker active IP conflict', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('selectClient flags the encounter type of the active tracker and enters the conflict step', async () => {
        const store = useEdVisitTracker()
        EdVisitTrackerService.getClient.mockResolvedValue({
            patient_id: 5,
            has_active_tracker: true,
            active_tracker_id: 11,
            active_tracker_encounter_type: 'IP',
        })

        await store.selectClient({ patient_id: 5 })

        expect(store.wizardStep).toBe('conflict')
        expect(store.activeTrackerConflict).toEqual({
            tracker_id: 11,
            encounter_type: 'IP',
        })
    })

    it('submitTracker routes a 409 for an active IP tracker to the conflict step', async () => {
        const store = useEdVisitTracker()
        store.selectedClient = { patient_id: 5 }
        EdVisitTrackerService.createTracker.mockRejectedValue({
            response: {
                status: 409,
                data: { tracker_id: 11, encounter_type: 'IP' },
            },
        })

        await store.submitTracker()

        expect(store.wizardStep).toBe('conflict')
        expect(store.activeTrackerConflict).toEqual({
            tracker_id: 11,
            encounter_type: 'IP',
        })
        expect(store.tracker).toBeNull()
    })
})

describe('useEdVisitTracker contacts (PD-3655)', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('submitContact creates a contact, appends it to the tracker and closes the modal', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER, contacts: [{ id: 1, contactName: 'A' }] }
        const saved = { id: 2, contactName: 'B' }
        EdVisitTrackerService.createContact.mockResolvedValue(saved)

        store.openAddContact()
        await store.submitContact({ contactName: 'B' })

        expect(EdVisitTrackerService.createContact).toHaveBeenCalledWith(42, {
            contactName: 'B',
        })
        expect(store.tracker.contacts).toEqual([
            { id: 1, contactName: 'A' },
            saved,
        ])
        expect(store.contactModalOpen).toBe(false)
    })

    it('submitContact updates the edited contact in place', async () => {
        const store = useEdVisitTracker()
        store.tracker = {
            ...TRACKER,
            contacts: [
                { id: 1, contactName: 'A' },
                { id: 2, contactName: 'B' },
            ],
        }
        const saved = { id: 2, contactName: 'B2' }
        EdVisitTrackerService.updateContact.mockResolvedValue(saved)

        store.openEditContact(store.tracker.contacts[1])
        await store.submitContact({ contactName: 'B2' })

        expect(EdVisitTrackerService.updateContact).toHaveBeenCalledWith(
            42,
            2,
            { contactName: 'B2' },
        )
        expect(store.tracker.contacts.map((c) => c.contactName)).toEqual([
            'A',
            'B2',
        ])
        expect(store.editingContact).toBeNull()
    })
})

describe('useEdVisitTracker notes (PD-3972)', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('submitNote posts the trimmed text, refreshes the open tracker panel and closes the modal', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        store.openCreateNote()
        const notes = [{ id: 1, notes: 'hello' }]
        EdVisitTrackerService.createNote.mockResolvedValue(notes)

        expect(await store.submitNote('  hello  ')).toBe(true)

        expect(EdVisitTrackerService.createNote).toHaveBeenCalledWith(
            42,
            'hello',
        )
        expect(store.trackerNotes).toEqual(notes)
        expect(store.noteModalOpen).toBe(false)
    })

    it('submitNote ignores blank text', async () => {
        const store = useEdVisitTracker()
        store.openCreateNoteForTracker(42)

        expect(await store.submitNote('   ')).toBe(false)
        expect(EdVisitTrackerService.createNote).not.toHaveBeenCalled()
        expect(store.noteModalOpen).toBe(true)
    })

    it('does not overwrite the panel when the note was added from the bucket for another tracker', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        store.trackerNotes = [{ id: 9 }]
        store.openCreateNoteForTracker(99)
        EdVisitTrackerService.createNote.mockResolvedValue([{ id: 1 }])

        await store.submitNote('x')

        expect(store.trackerNotes).toEqual([{ id: 9 }])
    })
})

describe('useEdVisitTracker sick plan', () => {
    const PLAN = { items: [{ id: 5, sickPlanType: 'Hydration' }] }

    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('loadSickPlan merges the plan onto the tracker', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        EdVisitTrackerService.getSickPlan.mockResolvedValue(PLAN)

        await store.loadSickPlan()

        expect(EdVisitTrackerService.getSickPlan).toHaveBeenCalledWith(42)
        expect(store.tracker.sickPlan).toEqual(PLAN)
    })

    it('loadSickPlan does nothing without a tracker', async () => {
        const store = useEdVisitTracker()

        await store.loadSickPlan()

        expect(EdVisitTrackerService.getSickPlan).not.toHaveBeenCalled()
    })

    it('submitSickPlanItem saves the item, stores the plan and closes the modal', async () => {
        const store = useEdVisitTracker()
        store.tracker = { ...TRACKER }
        const updated = { items: [{ id: 5, completed: true }] }
        const notes = [{ id: 1, notes: 'Sick Plan (Hydration): Completed?' }]
        EdVisitTrackerService.updateSickPlanItem.mockResolvedValue(updated)
        EdVisitTrackerService.getTrackerNotes.mockResolvedValue(notes)
        store.openEditSickPlanItem(PLAN.items[0])

        await store.submitSickPlanItem({ completed: true })

        expect(EdVisitTrackerService.updateSickPlanItem).toHaveBeenCalledWith(
            42,
            5,
            { completed: true },
        )
        expect(store.tracker.sickPlan).toEqual(updated)
        expect(EdVisitTrackerService.getTrackerNotes).toHaveBeenCalledTimes(1)
        expect(store.trackerNotes).toEqual(notes)
        expect(store.sickPlanModalOpen).toBe(false)
        expect(store.editingSickPlanItem).toBeNull()
    })

    it('opening the create modal (reset) clears the sick plan modal state', () => {
        const store = useEdVisitTracker()
        EdVisitTrackerService.getAssignees.mockResolvedValue([])
        store.openEditSickPlanItem(PLAN.items[0])

        store.openCreateModal()

        expect(store.sickPlanModalOpen).toBe(false)
        expect(store.editingSickPlanItem).toBeNull()
    })
})

describe('ED Visit tracker client profile hand-off', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        window.getClientDetails = vi.fn()
    })

    afterEach(() => {
        delete window.getClientDetails
    })

    function openTracker() {
        const store = useEdVisitTracker()
        store.tracker = { tracker_id: '#1', patient_id: 9001, status: 'Open' }
        store.wizardStep = 'open'
        store.isModalOpen = true

        return store
    }

    it('hasUnsavedChanges is true while any source is dirty and false once all are clean', () => {
        const store = useEdVisitTracker()
        expect(store.hasUnsavedChanges).toBe(false)

        store.setUnsaved('currentInfo', true)
        store.setUnsaved('outcome', true)
        store.setUnsaved('currentInfo', false)
        expect(store.hasUnsavedChanges).toBe(true)

        store.setUnsaved('outcome', false)
        expect(store.hasUnsavedChanges).toBe(false)
    })

    it('opens the profile right after closing the tracker when nothing is unsaved', async () => {
        const store = openTracker()

        await store.openClientProfile()

        expect(store.isModalOpen).toBe(false)
        expect(store.clientProfileConfirmOpen).toBe(false)
        expect(window.getClientDetails).toHaveBeenCalledWith(9001)
    })

    it('calls getClientDetails only after the tracker modal is closed', async () => {
        const store = openTracker()
        window.getClientDetails = vi.fn(() => {
            expect(store.isModalOpen).toBe(false)
        })

        await store.openClientProfile()

        expect(window.getClientDetails).toHaveBeenCalledTimes(1)
    })

    it('asks first when there are unsaved changes and does nothing else', async () => {
        const store = openTracker()
        store.setUnsaved('outcome', true)

        await store.openClientProfile()

        expect(store.clientProfileConfirmOpen).toBe(true)
        expect(store.isModalOpen).toBe(true)
        expect(window.getClientDetails).not.toHaveBeenCalled()
    })

    it('cancel keeps the tracker open and does not open the profile', async () => {
        const store = openTracker()
        store.setUnsaved('currentInfo', true)
        await store.openClientProfile()

        store.cancelClientProfile()

        expect(store.clientProfileConfirmOpen).toBe(false)
        expect(store.isModalOpen).toBe(true)
        expect(window.getClientDetails).not.toHaveBeenCalled()
    })

    it('confirm closes the tracker and opens the profile', async () => {
        const store = openTracker()
        store.setUnsaved('currentInfo', true)
        await store.openClientProfile()

        await store.confirmClientProfile()

        expect(store.clientProfileConfirmOpen).toBe(false)
        expect(store.isModalOpen).toBe(false)
        expect(window.getClientDetails).toHaveBeenCalledWith(9001)
    })

    it('does nothing without a patient_id', async () => {
        const store = useEdVisitTracker()
        store.tracker = { tracker_id: '#1', status: 'Open' }
        store.isModalOpen = true

        await store.openClientProfile()

        expect(store.isModalOpen).toBe(true)
        expect(window.getClientDetails).not.toHaveBeenCalled()
    })

    it('opening another tracker forgets the previous tracker unsaved state', () => {
        const store = openTracker()
        store.setUnsaved('currentInfo', true)

        store.openCreateModal()

        expect(store.hasUnsavedChanges).toBe(false)
    })
})
