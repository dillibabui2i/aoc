import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdSickPlanTab from '@/modules/vbc/components/EdVisitTracker/EdSickPlanTab.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

vi.mock('@/modules/vbc/services/edVisitTracker.service.js', () => ({
    EdVisitTrackerService: { getSickPlan: vi.fn().mockResolvedValue(null) },
}))

const ITEMS = [
    {
        id: 1,
        sickPlanType: 'Hydration',
        completed: false,
        completedBy: null,
        notes: 'n1',
    },
    {
        id: 2,
        sickPlanType: 'Pain',
        completed: true,
        completedBy: 'Emma Watson',
        notes: 'n2',
    },
]

function mountTab(status = 'In Progress') {
    const pinia = createTestingPinia({
        stubActions: true,
        createSpy: vi.fn,
        initialState: {
            edVisitTracker: {
                tracker: {
                    tracker_id: 9001,
                    status,
                    sickPlan: { items: ITEMS },
                },
            },
        },
    })
    const wrapper = mount(EdSickPlanTab, {
        attachTo: document.body,
        global: { plugins: [pinia] },
    })
    return { wrapper, store: useEdVisitTracker() }
}

describe('EdSickPlanTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    it('loads the plan on mount and renders a row per item', () => {
        const { wrapper, store } = mountTab()
        expect(store.loadSickPlan).toHaveBeenCalled()
        expect(wrapper.findAll('tbody tr')).toHaveLength(2)
        expect(wrapper.text()).toContain('Hydration')
        expect(wrapper.text()).toContain('Yes')
        expect(wrapper.text()).toContain('No')
        expect(wrapper.text()).toContain('Emma Watson')
    })

    it('shows "-" when Completed by is empty', () => {
        const { wrapper } = mountTab()
        expect(wrapper.findAll('tbody tr')[0].text()).toContain('-')
    })

    it('the calendar icon opens the item, also when the tracker is not In Progress', async () => {
        const { wrapper, store } = mountTab('Closed')
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditSickPlanItem).toHaveBeenCalledWith(ITEMS[0])
    })
})
