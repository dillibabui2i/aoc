import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import SickPlanTab from '@/modules/vbc/components/HospitalizationTracker/SickPlanTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// SickPlanTab re-fetches on mount (see the component's onMounted), so the real
// service call needs mocking here even though this suite doesn't assert on it.
vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: { getSickPlan: vi.fn().mockResolvedValue(null) },
    CURRENT_USER: 'Anna Lopatnova',
}))

const ITEMS = [
    {
        id: 'sickplan-1',
        sickPlanType: 'Hydration',
        completed: false,
        completedBy: null,
        notes: 'n1',
    },
    {
        id: 'sickplan-2',
        sickPlanType: 'Pain',
        completed: true,
        completedBy: 'Emma Watson',
        notes: 'n2',
    },
]

describe('SickPlanTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(SickPlanTab, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('renders a row per Sick Plan item', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            sickPlan: { clientId: '99999-PDN', items: ITEMS },
        })
        expect(wrapper.findAll('tbody tr')).toHaveLength(2)
        expect(wrapper.text()).toContain('Hydration')
        expect(wrapper.text()).toContain('Pain')
        expect(wrapper.text()).toContain('Yes')
        expect(wrapper.text()).toContain('Emma Watson')
    })

    it('clicking the calendar icon calls store.openEditSickPlanItem with the row', async () => {
        const { wrapper, store } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            sickPlan: { clientId: '99999-PDN', items: ITEMS },
        })
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditSickPlanItem).toHaveBeenCalledWith(ITEMS[0])
    })

    it('shows the calendar icon action even when the tracker is Open (read-only)', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'Open',
            sickPlan: { clientId: '99999-PDN', items: ITEMS },
        })
        expect(wrapper.find('.fa-calendar').exists()).toBe(true)
    })
})
