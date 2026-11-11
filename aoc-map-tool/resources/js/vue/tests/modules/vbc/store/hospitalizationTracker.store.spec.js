import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: {
        searchClients: vi.fn(),
        getClient: vi.fn(),
        getAssignees: vi.fn(),
        createTracker: vi.fn(),
        getTrackerById: vi.fn(),
        saveField: vi.fn(),
        saveCurrentInfo: vi.fn(),
        getTrackerNotes: vi.fn(),
        getSickPlan: vi.fn(),
        updateOutcome: vi.fn(),
        updateOutcomeStatus: vi.fn(),
        createContact: vi.fn(),
        updateContact: vi.fn(),
        createDiagnosis: vi.fn(),
        updateDiagnosis: vi.fn(),
        updateWasDiagnosisMade: vi.fn(),
        getTrackerHistory: vi.fn(),
        updateSickPlanItem: vi.fn(),
        createEducationRecord: vi.fn(),
        updateEducationRecord: vi.fn(),
        updateStaffFamilyTrainingRequired: vi.fn(),
        createNote: vi.fn(),
    },
    CURRENT_USER: 'Anna Lopatnova',
}))

const CLIENT = {
    patient_id: 12345,
    patient_name: 'John Taylor',
    client_id: '12345-PDN',
}

describe('useHospitalizationTracker', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('openCreateModal resets wizard state and opens the modal', () => {
        const store = useHospitalizationTracker()
        store.openCreateModal()

        expect(store.isModalOpen).toBe(true)
        expect(store.wizardStep).toBe('search')
        expect(store.selectedClient).toBeNull()
        expect(store.tracker).toBeNull()
    })

    it('closeModal closes the modal without resetting wizard state', () => {
        const store = useHospitalizationTracker()
        store.openCreateModal()
        store.closeModal()

        expect(store.isModalOpen).toBe(false)
    })

    it('searchClients populates results from the service', async () => {
        HospitalizationTrackerService.searchClients.mockResolvedValue([CLIENT])
        const store = useHospitalizationTracker()

        await store.searchClients('john')

        expect(
            HospitalizationTrackerService.searchClients,
        ).toHaveBeenCalledWith('john')
        expect(store.clientSearchResults).toEqual([CLIENT])
        expect(store.clientSearchLoading).toBe(false)
    })

    it('selectClient moves to "confirmed" when no active tracker exists', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        const store = useHospitalizationTracker()

        await store.selectClient(CLIENT)

        expect(store.selectedClient).toMatchObject(CLIENT)
        expect(store.wizardStep).toBe('confirmed')
        expect(store.activeTrackerConflict).toBeNull()
    })

    it('selectClient moves to "conflict" when an active tracker exists', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: true,
            active_tracker_id: '#10087',
        })
        const store = useHospitalizationTracker()

        await store.selectClient(CLIENT)

        expect(store.wizardStep).toBe('conflict')
        expect(store.activeTrackerConflict).toEqual({ tracker_id: '#10087' })
    })

    it('searchAnotherClient clears the selection and returns to "search"', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: true,
            active_tracker_id: '#10087',
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)

        store.searchAnotherClient()

        expect(store.wizardStep).toBe('search')
        expect(store.selectedClient).toBeNull()
        expect(store.activeTrackerConflict).toBeNull()
    })

    it('openExistingTracker loads the conflicting tracker and moves to "open"', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: true,
            active_tracker_id: '#10087',
        })
        HospitalizationTrackerService.getTrackerById.mockResolvedValue({
            tracker_id: '#10087',
            status: 'Open',
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)

        await store.openExistingTracker()

        expect(
            HospitalizationTrackerService.getTrackerById,
        ).toHaveBeenCalledWith('#10087')
        expect(store.tracker).toEqual({ tracker_id: '#10087', status: 'Open' })
        expect(store.wizardStep).toBe('open')
        expect(store.activeTrackerConflict).toBeNull()
    })

    it('submitTracker creates the tracker and moves to "open"', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            ...CLIENT,
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)

        await store.submitTracker()

        expect(
            HospitalizationTrackerService.createTracker,
        ).toHaveBeenCalledWith({
            patient_id: 12345,
        })
        expect(store.tracker.status).toBe('Open')
        expect(store.wizardStep).toBe('open')
    })

    it('autoSaveField updates the tracker and persists immediately', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)
        await store.submitTracker()

        await store.autoSaveField('facilityName', 'General Hospital')

        expect(store.tracker.facilityName).toBe('General Hospital')
        expect(HospitalizationTrackerService.saveField).toHaveBeenCalledWith(
            '#11123',
            'facilityName',
            'General Hospital',
        )
    })

    it('refreshTracker re-fetches and reassigns the currently loaded tracker', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            followUps: [],
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)
        await store.submitTracker()
        const previousWizardStep = store.wizardStep
        const previousIsModalOpen = store.isModalOpen
        HospitalizationTrackerService.getTrackerById.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            followUps: [{ id: 'followup-1', status: 'Open' }],
        })

        await store.refreshTracker()

        expect(
            HospitalizationTrackerService.getTrackerById,
        ).toHaveBeenCalledWith('#11123')
        expect(store.tracker.followUps).toHaveLength(1)
        expect(store.wizardStep).toBe(previousWizardStep)
        expect(store.isModalOpen).toBe(previousIsModalOpen)
    })

    it('refreshTracker does nothing when no tracker is loaded', async () => {
        const store = useHospitalizationTracker()

        await store.refreshTracker()

        expect(
            HospitalizationTrackerService.getTrackerById,
        ).not.toHaveBeenCalled()
    })

    it('submitCurrentInfo saves the whole section and reloads the Activity Log', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            admission_type: 'AVOIDABLE',
        })
        HospitalizationTrackerService.saveCurrentInfo.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            admission_type: 'PLANNED',
        })
        HospitalizationTrackerService.getTrackerNotes.mockResolvedValue([
            {
                id: 1,
                notes: 'Admission Type updated from Avoidable to Planned by Anna Lopatnova',
                author_name: 'Anna Lopatnova',
                created_at: '08/31/2026 10:48 AM',
            },
        ])
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)
        await store.submitTracker()

        const values = { admission_type: 'PLANNED', source: 'POINT_CLICK_CARE' }
        expect(await store.submitCurrentInfo(values)).toBe(true)

        expect(
            HospitalizationTrackerService.saveCurrentInfo,
        ).toHaveBeenCalledWith('#11123', values)
        expect(store.tracker.admission_type).toBe('PLANNED')
        // The log is the server's, not built locally.
        expect(store.trackerNotes).toHaveLength(1)
        expect(store.trackerNotes[0].notes).toContain('Admission Type updated')
    })

    it('submitCurrentInfo surfaces a 422 against the fields it names', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
        })
        HospitalizationTrackerService.saveCurrentInfo.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        facility_name: ['The Facility Name field is required.'],
                    },
                },
            },
        })
        const store = useHospitalizationTracker()
        await store.selectClient(CLIENT)
        await store.submitTracker()

        expect(await store.submitCurrentInfo({ facility_name: '' })).toBe(false)
        expect(store.formErrors.facility_name).toBe(
            'The Facility Name field is required.',
        )
    })

    async function openTracker(overrides = {}) {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            ...CLIENT,
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Open',
            ...overrides,
        })
        const store = useHospitalizationTracker()
        store.openCreateModal()
        await store.selectClient(CLIENT)
        await store.submitTracker()
        return store
    }

    it('submitOutcome moves the tracker to In Progress and reloads the Activity Log', async () => {
        const store = await openTracker()
        HospitalizationTrackerService.updateOutcomeStatus.mockResolvedValue({
            tracker_id: '#11123',
            status: 'In Progress',
        })
        HospitalizationTrackerService.getTrackerNotes.mockResolvedValue([
            {
                id: 1,
                notes: "In Progress by Anna Lopatnova at 09/04/2026 10:00 AM\nStatus changed to 'In Progress' by Anna Lopatnova",
                author_name: 'Anna Lopatnova',
                created_at: '09/04/2026 10:00 AM',
            },
        ])

        await store.submitOutcome({ outcome: 'In Progress', reason: null })

        expect(
            HospitalizationTrackerService.updateOutcomeStatus,
        ).toHaveBeenCalledWith('#11123', {
            outcome: 'In Progress',
            reason: null,
        })
        expect(store.tracker.status).toBe('In Progress')
        expect(store.trackerNotes).toHaveLength(1)
        expect(store.isModalOpen).toBe(true)
        expect(store.outcomeError).toBeNull()
    })

    it('submitSickPlanItem saves the item, reloads the notes and closes the modal', async () => {
        const store = await openTracker()
        const updated = { items: [{ id: 5, completed: true }] }
        const notes = [{ id: 1, notes: 'Sick Plan (Hydration): Completed?' }]
        HospitalizationTrackerService.updateSickPlanItem.mockResolvedValue(
            updated,
        )
        HospitalizationTrackerService.getTrackerNotes.mockResolvedValue(notes)
        store.openEditSickPlanItem({ id: 5 })

        await store.submitSickPlanItem({ completed: true })

        expect(
            HospitalizationTrackerService.updateSickPlanItem,
        ).toHaveBeenCalledWith('#11123', 5, { completed: true })
        expect(store.tracker.sickPlan).toEqual(updated)
        expect(
            HospitalizationTrackerService.getTrackerNotes,
        ).toHaveBeenCalledWith('#11123')
        expect(store.trackerNotes).toEqual(notes)
        expect(store.sickPlanModalOpen).toBe(false)
    })

    it('submitOutcome closes the tracker with a reason and closes the modal', async () => {
        const store = await openTracker()
        HospitalizationTrackerService.updateOutcomeStatus.mockResolvedValue({
            tracker_id: '#11123',
            status: 'Closed',
        })
        HospitalizationTrackerService.getTrackerNotes.mockResolvedValue([])

        await store.submitOutcome({ outcome: 'Closed', reason: 'ED Visit' })

        expect(
            HospitalizationTrackerService.updateOutcomeStatus,
        ).toHaveBeenCalledWith('#11123', {
            outcome: 'Closed',
            reason: 'ED Visit',
        })
        expect(store.tracker.status).toBe('Closed')
        expect(store.isModalOpen).toBe(false)
    })

    it('submitOutcome blocks Closed without a reason', async () => {
        const store = await openTracker()

        await store.submitOutcome({ outcome: 'Closed', reason: '' })

        expect(
            HospitalizationTrackerService.updateOutcomeStatus,
        ).not.toHaveBeenCalled()
        expect(store.outcomeError).toBe('This field is required.')
    })

    it('submitOutcome surfaces the server message when a transition is rejected', async () => {
        const store = await openTracker()
        HospitalizationTrackerService.updateOutcomeStatus.mockRejectedValue({
            response: {
                status: 409,
                data: {
                    message:
                        'Complete the following Current Hospitalization Info fields before moving to In Progress: Facility Name.',
                },
            },
        })

        await store.submitOutcome({ outcome: 'In Progress', reason: null })

        expect(store.outcomeError).toBe(
            'Complete the following Current Hospitalization Info fields before moving to In Progress: Facility Name.',
        )
        expect(store.tracker.status).toBe('Open')
    })
})

describe('Hospitalization tracker client profile hand-off', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        window.getClientDetails = vi.fn()
    })

    afterEach(() => {
        delete window.getClientDetails
    })

    function openTracker() {
        const store = useHospitalizationTracker()
        store.tracker = { tracker_id: '#1', patient_id: 9001, status: 'Open' }
        store.wizardStep = 'open'
        store.isModalOpen = true

        return store
    }

    it('hasUnsavedChanges is true while any source is dirty and false once all are clean', () => {
        const store = useHospitalizationTracker()
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
        const store = useHospitalizationTracker()
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
