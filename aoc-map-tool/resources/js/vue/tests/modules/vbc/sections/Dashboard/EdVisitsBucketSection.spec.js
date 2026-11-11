import { describe, it, expect, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdVisitsBucketSection from '@/modules/vbc/sections/Dashboard/EdVisitsBucketSection.vue'
import { useEdVisits } from '@/modules/vbc/store/Dashboard/edVisits.store.js'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const ROWS = [
    {
        id: 1,
        tracker_id: 101,
        patient_id: 9001,
        status: 'In Progress',
        follow_up_status: 'Scheduled',
        is_overdue: true,
    },
    {
        id: 2,
        tracker_id: 102,
        patient_id: 9002,
        status: 'In Progress',
        follow_up_status: null,
        is_overdue: false,
    },
]

describe('EdVisitsBucketSection.vue', () => {
    afterEach(() => {
        document.body.innerHTML = ''
        delete window.getClientDetails
    })

    async function mountComponent() {
        const pinia = createTestingPinia({ createSpy: vi.fn })
        const store = useEdVisits(pinia)
        store.list = ROWS
        const wrapper = mount(EdVisitsBucketSection, {
            global: {
                plugins: [pinia],
                stubs: {
                    EdVisitsBucketFilters: true,
                    HospitalizationTrackerModal: true,
                    EdVisitTrackerModal: true,
                },
            },
        })
        await wrapper.vm.$nextTick()
        return wrapper
    }

    it('loads the bucket on mount', async () => {
        await mountComponent()

        expect(useEdVisits().fetchAll).toHaveBeenCalled()
    })

    it('renders the AC12 columns, with Action last', async () => {
        const wrapper = await mountComponent()

        // Sortable headers also render the sort glyphs (▲▼); only the label matters here.
        const headers = wrapper
            .findAll('thead th')
            .map((th) => th.text().replace(/[▲▼]/g, '').trim())
        expect(headers).toEqual([
            'Patient Name',
            'Patient ID',
            'Patient Level',
            'Tracker ID',
            'Admission Date',
            'Follow-up Status',
            'Assignee',
            'Terminal Payer',
            'Branch',
            'Action',
        ])
    })

    it('highlights overdue rows only', async () => {
        const wrapper = await mountComponent()

        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].classes()).toContain('vbc-row--overdue')
        expect(rows[1].classes()).not.toContain('vbc-row--overdue')
    })

    it('calendar icon opens the tracker', async () => {
        const wrapper = await mountComponent()

        await wrapper
            .findAll('tbody tr')[0]
            .find('a[title="Open Tracker"]')
            .trigger('click')

        // Rows are ED Visit trackers — opened through the ED store, not ICM's.
        expect(useEdVisitTracker().loadTrackerById).toHaveBeenCalledWith(101)
    })

    it('user icon opens the client profile', async () => {
        window.getClientDetails = vi.fn()
        const wrapper = await mountComponent()

        await wrapper
            .findAll('tbody tr')[0]
            .find('a[title="View Client Profile"]')
            .trigger('click')

        expect(window.getClientDetails).toHaveBeenCalledWith(9001)
    })

    it('page icon opens Create Note for the tracker', async () => {
        const wrapper = await mountComponent()

        await wrapper
            .findAll('tbody tr')[0]
            .find('a[title="Notes"]')
            .trigger('click')

        expect(
            useEdVisitTracker().openCreateNoteForTracker,
        ).toHaveBeenCalledWith(101)
    })

    it('hides the Notes icon for a Completed/Closed/Cancelled row', async () => {
        const pinia = createTestingPinia({ createSpy: vi.fn })
        useEdVisits(pinia).list = [{ ...ROWS[0], status: 'Closed' }]
        const wrapper = mount(EdVisitsBucketSection, {
            global: {
                plugins: [pinia],
                stubs: {
                    EdVisitsBucketFilters: true,
                    HospitalizationTrackerModal: true,
                    EdVisitTrackerModal: true,
                },
            },
        })
        await wrapper.vm.$nextTick()

        expect(wrapper.find('a[title="Notes"]').exists()).toBe(false)
    })

    it('always hosts the ED Visit tracker modal, plus the ICM tracker modal when the ICM Hospitalization bucket is not on the page', async () => {
        const wrapper = await mountComponent()

        expect(
            wrapper.findComponent({ name: 'EdVisitTrackerModal' }).exists(),
        ).toBe(true)
        expect(
            wrapper
                .findComponent({ name: 'HospitalizationTrackerModal' })
                .exists(),
        ).toBe(true)
    })

    it('leaves the ICM tracker modal to the ICM Hospitalization bucket when it is on the page', async () => {
        const icmMount = document.createElement('div')
        icmMount.id = 'vbc-bucket-hospitalization'
        document.body.appendChild(icmMount)

        const wrapper = await mountComponent()

        expect(
            wrapper
                .findComponent({ name: 'HospitalizationTrackerModal' })
                .exists(),
        ).toBe(false)
        expect(
            wrapper.findComponent({ name: 'EdVisitTrackerModal' }).exists(),
        ).toBe(true)
    })
})
