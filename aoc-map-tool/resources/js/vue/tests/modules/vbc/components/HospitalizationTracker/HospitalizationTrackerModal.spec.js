import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import HospitalizationTrackerModal from '@/modules/vbc/components/HospitalizationTracker/HospitalizationTrackerModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

// Every loader the modal tree (title bar, info grid, tabs, their modals) can
// call is resolved to an empty result by default, so nothing reaches the network.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                searchClients: vi.fn().mockResolvedValue([]),
                getClient: vi.fn(),
                getAssignees: vi.fn().mockResolvedValue([]),
                getContactTypes: vi.fn().mockResolvedValue([]),
                getTrainingTypes: vi.fn().mockResolvedValue([]),
                getTrainingSources: vi.fn().mockResolvedValue([]),
                createTracker: vi.fn(),
                getTrackerById: vi.fn(),
                getTrackerNotes: vi.fn().mockResolvedValue([]),
                getTrackerHistory: vi.fn().mockResolvedValue([]),
                getSickPlan: vi.fn().mockResolvedValue({ items: [] }),
                saveField: vi.fn(),
                saveCurrentInfo: vi.fn(),
            },
        }
    },
)

vi.mock(
    '@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js',
    () => ({
        careCoordinationActivitiesService: {
            getAll: vi.fn().mockResolvedValue({ data: [] }),
            getTypes: vi.fn().mockResolvedValue([]),
        },
    }),
)

vi.mock('@/modules/vbc/store/allHospitalization.store.js', () => ({
    useAllHospitalization: () => ({ fetchAll: vi.fn() }),
}))

// What the Client Name autocomplete hands to selectClient().
const CLIENT = { patient_id: 501, patient_name: 'John Taylor' }

describe('HospitalizationTrackerModal', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
        vi.clearAllMocks()
        document.body.innerHTML = ''
    })

    // UiModal renders via <teleport to="body">, so its content lives
    // outside `wrapper.element` — query `document.body` for it instead.
    function mountComponent() {
        return mount(HospitalizationTrackerModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
    }

    function openTracker(store, tracker) {
        store.openCreateModal()
        store.wizardStep = 'open'
        store.tracker = { tracker_id: 11123, ...tracker }
    }

    async function openTab(label) {
        await new Promise((r) => window.setTimeout(r))
        Array.from(document.body.querySelectorAll('.tracker-tabs__tab'))
            .find((el) => el.textContent.trim() === label)
            .click()
        await new Promise((r) => window.setTimeout(r))
    }

    it('happy path: selecting a conflict-free client, submitting, renders the Open state', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            patient_id: 501,
            patient_name: 'John Taylor',
            has_active_tracker: false,
        })
        HospitalizationTrackerService.createTracker.mockResolvedValue({
            tracker_id: 11123,
            status: 'Open',
            patient_name: 'John Taylor',
            level_label: 'Level I',
        })

        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.openCreateModal()
        await wrapper.vm.$nextTick()

        await store.selectClient(CLIENT)
        await wrapper.vm.$nextTick()
        expect(HospitalizationTrackerService.getClient).toHaveBeenCalledWith(
            501,
        )
        expect(store.wizardStep).toBe('confirmed')

        await store.submitTracker()
        await wrapper.vm.$nextTick()

        expect(
            HospitalizationTrackerService.createTracker,
        ).toHaveBeenCalledWith(expect.objectContaining({ patient_id: 501 }))
        expect(store.wizardStep).toBe('open')
        expect(document.body.textContent).toContain(
            'Current Hospitalization Info',
        )
        expect(document.body.textContent).toContain('Hospitalization History')
        expect(document.body.textContent).toContain('Sick Plan')
        expect(document.body.textContent).toContain('Follow-up')
        expect(document.body.textContent).toContain('Outcome')
        expect(document.body.querySelector('.notes-sidebar')).not.toBeNull()
    })

    it('conflict path: "Open the active tracker" cancels creation and opens the existing tracker', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            patient_id: 501,
            patient_name: 'John Taylor',
            has_active_tracker: true,
            active_tracker_id: 10087,
            active_tracker_encounter_type: 'IP',
        })
        HospitalizationTrackerService.getTrackerById.mockResolvedValue({
            tracker_id: 10087,
            status: 'Open',
            patient_name: 'John Doe',
            level_label: 'Level II',
        })

        mountComponent()
        const store = useHospitalizationTracker()
        store.openCreateModal()
        await store.selectClient(CLIENT)
        await new Promise((r) => window.setTimeout(r))

        expect(store.wizardStep).toBe('conflict')
        expect(document.body.querySelector('.modal')).not.toBeNull()

        await store.openExistingTracker()
        await new Promise((r) => window.setTimeout(r))

        expect(
            HospitalizationTrackerService.createTracker,
        ).not.toHaveBeenCalled()
        expect(
            HospitalizationTrackerService.getTrackerById,
        ).toHaveBeenCalledWith(10087)
        expect(store.wizardStep).toBe('open')
        expect(store.tracker.tracker_id).toBe(10087)
    })

    it('conflict path: "Search another client" returns to the search step', async () => {
        HospitalizationTrackerService.getClient.mockResolvedValue({
            patient_id: 501,
            has_active_tracker: true,
            active_tracker_id: 10087,
        })

        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.openCreateModal()
        await store.selectClient(CLIENT)
        await wrapper.vm.$nextTick()

        store.searchAnotherClient()
        await wrapper.vm.$nextTick()

        expect(store.wizardStep).toBe('search')
        expect(store.selectedClient).toBeNull()
    })

    it('renders the flat 4-tab layout for an Open tracker', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'Open' })
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain(
            'Current Hospitalization Info',
        )
        expect(document.body.textContent).not.toContain('Contacts')
    })

    it('renders the nested 9-item tab layout once In Progress', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'In Progress' })
        await wrapper.vm.$nextTick()

        ;[
            'Hospitalization Info',
            'Current Hospitalization Info',
            'Hospitalization History',
            'Contacts',
            'Diagnosis',
            'Sick Plan',
            'Care Coordination',
            'Education',
            'Follow-up',
        ].forEach((label) => {
            expect(document.body.textContent).toContain(label)
        })
    })

    it('keeps the nested tab layout for a tracker closed after In Progress (Activity Log entry)', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'Closed' })
        store.trackerNotes = [
            {
                id: 1,
                notes: "Status changed to 'In Progress' by Anna Lopatnova",
                author_name: 'Anna Lopatnova',
                commented_at: '01/01/2026 10:00 AM',
            },
        ]
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain('Contacts')
    })

    it('keeps the nested tab layout for a tracker flagged wasInProgress', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'Closed', wasInProgress: true })
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).toContain('Contacts')
    })

    it('keeps the flat tab layout for a tracker closed directly from Open', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'Closed', wasInProgress: false })
        await wrapper.vm.$nextTick()

        expect(document.body.textContent).not.toContain('Contacts')
    })

    it('passes the tracker notes through to the Notes sidebar', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'In Progress' })
        store.trackerNotes = [
            {
                id: 1,
                notes: 'Family called to confirm discharge plan.',
                author_name: 'Anna Lopatnova',
                commented_at: '01/01/2026 10:00 AM',
            },
        ]
        await wrapper.vm.$nextTick()

        const sidebar = document.body.querySelector('.notes-sidebar')
        expect(sidebar.textContent).toContain(
            'Family called to confirm discharge plan.',
        )
        expect(sidebar.textContent).toContain('Anna Lopatnova commented')
    })

    it('renders ContactsTab (not a placeholder) when the Contacts tab is opened', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'In Progress', contacts: [] })
        await openTab('Contacts')

        expect(document.body.textContent).toContain('Add Contact')
        expect(document.body.textContent).not.toContain(
            'will be available in a future update',
        )
    })

    it('renders DiagnosisTab (not a placeholder) when the Diagnosis tab is opened', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, {
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: [],
        })
        await openTab('Diagnosis')

        expect(document.body.textContent).toContain('Was the Diagnosis Made?')
        expect(document.body.textContent).not.toContain(
            'will be available in a future update',
        )
    })

    it('renders SickPlanTab (not a placeholder) when the Sick Plan tab is opened', async () => {
        // The tab loads the client's Sick Plan itself when it mounts.
        HospitalizationTrackerService.getSickPlan.mockResolvedValue({
            items: [{ id: 'sickplan-1', sickPlanType: 'Hydration' }],
        })
        mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, { status: 'In Progress' })
        await openTab('Sick Plan')

        expect(HospitalizationTrackerService.getSickPlan).toHaveBeenCalledWith(
            11123,
        )
        expect(document.body.textContent).toContain('Hydration')
        expect(document.body.textContent).not.toContain(
            'will be available in a future update',
        )
    })

    it('renders EducationTab (not a placeholder) when the Education tab is opened', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, {
            status: 'In Progress',
            staff_family_training_required: false,
            education_records: [],
        })
        await openTab('Education')

        expect(document.body.textContent).toContain(
            'Staff / Family Training/Education required?',
        )
        expect(document.body.textContent).not.toContain(
            'will be available in a future update',
        )
    })

    it('renders FollowUpTab (not a placeholder) when the Follow-up tab is opened', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        openTracker(store, {
            status: 'In Progress',
            followUps: [
                {
                    id: 'followup-1',
                    followUpDate: '3/01/2026 12:00 AM',
                    assignedTo: 'Axel Hawker',
                    status: 'Scheduled',
                    createdAt: 1,
                },
            ],
        })
        await openTab('Follow-up')

        expect(document.body.textContent).toContain('Axel Hawker')
        expect(document.body.textContent).not.toContain(
            'will be available in a future update',
        )
    })
})
