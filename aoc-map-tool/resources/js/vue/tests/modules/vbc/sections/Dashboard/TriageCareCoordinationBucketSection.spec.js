import { describe, it, expect, vi, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TriageCareCoordinationBucketSection from '@/modules/vbc/sections/Dashboard/TriageCareCoordinationBucketSection.vue'
import { useTriageCareCoordination } from '@/modules/vbc/store/Dashboard/triageCareCoordination.store.js'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import { useCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/careCoordinationActivities.store.js'

const ROWS = [
    { id: 7, patient_id: 9001, provider: null, is_overdue: true },
    { id: 8, patient_id: 9002, provider: null, is_overdue: false },
]

describe('TriageCareCoordinationBucketSection.vue', () => {
    afterEach(() => {
        document.body.innerHTML = ''
        delete window.getClientDetails
    })

    async function mountComponent() {
        const pinia = createTestingPinia({ createSpy: vi.fn })
        useTriageCareCoordination(pinia).list = ROWS
        const wrapper = mount(TriageCareCoordinationBucketSection, {
            global: {
                plugins: [pinia],
                stubs: {
                    TriageCareCoordinationFilters: true,
                    EdCareCoordinationModal: true,
                },
            },
        })
        await wrapper.vm.$nextTick()
        return wrapper
    }

    it('loads its own bucket on mount, not the ICM one', async () => {
        await mountComponent()

        expect(useTriageCareCoordination().fetchAll).toHaveBeenCalled()
        expect(useCareCoordinationActivities().fetchAll).not.toHaveBeenCalled()
    })

    it('renders the AC15 columns, with Action last', async () => {
        const wrapper = await mountComponent()

        // Sortable headers also render the sort glyphs (▲▼); only the label matters here.
        const headers = wrapper
            .findAll('thead th')
            .map((th) => th.text().replace(/[▲▼]/g, '').trim())
        expect(headers).toEqual([
            'Created Date',
            'Client Name',
            'Client ID',
            'Hospital',
            'Type',
            'Provider',
            'Assigned To',
            'Due Date',
            'Action',
        ])
    })

    it('highlights overdue rows only', async () => {
        const wrapper = await mountComponent()

        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].classes()).toContain('vbc-row--overdue')
        expect(rows[1].classes()).not.toContain('vbc-row--overdue')
    })

    it('calendar icon opens the activity and refreshes this bucket after a save', async () => {
        const wrapper = await mountComponent()
        const activityStore = useEdCareCoordinationActivities()

        await wrapper
            .findAll('tbody tr')[0]
            .find('a[title="Open Care Coordination"]')
            .trigger('click')

        expect(activityStore.openActivity).toHaveBeenCalledWith(
            ROWS[0],
            expect.any(Function),
        )
        activityStore.openActivity.mock.calls[0][1]()
        expect(useTriageCareCoordination().fetchAll).toHaveBeenCalledTimes(2)
    })

    it('"+" opens a new activity and refreshes this bucket after a save', async () => {
        const wrapper = await mountComponent()
        const activityStore = useEdCareCoordinationActivities()

        wrapper
            .findComponent({ name: 'TriageCareCoordinationFilters' })
            .vm.$emit('add')

        expect(activityStore.openCreate).toHaveBeenCalledWith(
            expect.any(Function),
        )
        activityStore.openCreate.mock.calls[0][0]()
        expect(useTriageCareCoordination().fetchAll).toHaveBeenCalledTimes(2)
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

    it('page icon opens Create Note for the row', async () => {
        const wrapper = await mountComponent()

        await wrapper
            .findAll('tbody tr')[0]
            .find('a[title="Notes"]')
            .trigger('click')

        expect(useTriageCareCoordination().openCreateNote).toHaveBeenCalledWith(
            ROWS[0],
        )
    })

    it('hosts the ED Care Coordination modal (not the ICM one), whatever else is on the page', async () => {
        const icmMount = document.createElement('div')
        icmMount.id = 'vbc-bucket-care-coordination'
        document.body.appendChild(icmMount)

        const wrapper = await mountComponent()

        expect(
            wrapper.findComponent({ name: 'EdCareCoordinationModal' }).exists(),
        ).toBe(true)
        expect(
            wrapper.findComponent({ name: 'CareCoordinationModal' }).exists(),
        ).toBe(false)
    })
})
