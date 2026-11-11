import { describe, it, expect, vi, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdCareCoordinationTab from '@/modules/vbc/components/EdVisitTracker/EdCareCoordinationTab.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'

vi.mock('@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js', () => ({
    edCareCoordinationActivitiesService: { getAll: vi.fn() },
}))

const ROWS = [
    { id: 1, due_date: '10/09/2026 09:00 AM', coordination_type: 'Referral', is_completed: false },
    { id: 2, due_date: '10/08/2026 09:00 AM', coordination_type: 'Transport', is_completed: true },
]

describe('EdCareCoordinationTab.vue', () => {
    afterEach(() => {
        document.body.innerHTML = ''
        vi.clearAllMocks()
    })

    async function mountTab() {
        edCareCoordinationActivitiesService.getAll.mockResolvedValue({ data: ROWS })
        const pinia = createTestingPinia({ createSpy: vi.fn, stubActions: false })
        useEdVisitTracker(pinia).tracker = { tracker_id: 55 }
        const wrapper = mount(EdCareCoordinationTab, {
            global: { plugins: [pinia], stubs: { EdCareCoordinationModal: true } },
        })
        await flushPromises()
        return wrapper
    }

    it("loads the ED tracker's own activities", async () => {
        await mountTab()

        expect(edCareCoordinationActivitiesService.getAll).toHaveBeenCalledWith({
            hospitalization_tracker_id: 55,
            per_page: 100,
            sort_by: 'due_date',
            sort_dir: 'asc',
        })
    })

    it('sorts by due date ascending and shows Completed? as Yes/No', async () => {
        const wrapper = await mountTab()

        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].text()).toContain('Transport')
        expect(rows[0].text()).toContain('Yes')
        expect(rows[1].text()).toContain('Referral')
        expect(rows[1].text()).toContain('No')
    })

    it('the calendar icon opens the activity in the ED modal and re-fetches after a save', async () => {
        const wrapper = await mountTab()
        const store = useEdCareCoordinationActivities()
        store.openActivity = vi.fn()

        await wrapper.findAll('tbody tr')[0].find('a').trigger('click')

        expect(store.openActivity).toHaveBeenCalledWith(ROWS[1], expect.any(Function))
        await store.openActivity.mock.calls[0][1]()
        expect(edCareCoordinationActivitiesService.getAll).toHaveBeenCalledTimes(2)
    })

    it('hosts the modal itself, unless the Triage Care Coordination bucket already does', async () => {
        expect((await mountTab()).findComponent({ name: 'EdCareCoordinationModal' }).exists()).toBe(true)

        const bucket = document.createElement('div')
        bucket.id = 'vbc-bucket-triage-care-coordination'
        document.body.appendChild(bucket)

        expect((await mountTab()).findComponent({ name: 'EdCareCoordinationModal' }).exists()).toBe(false)
    })
})
