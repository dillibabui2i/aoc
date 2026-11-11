import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import FollowUpTab from '@/modules/vbc/components/HospitalizationTracker/FollowUpTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'

// FollowUpModal (rendered inside the tab) loads its Assigned To options on mount.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                getAssignees: vi.fn().mockResolvedValue([]),
            },
        }
    },
)

const FOLLOW_UPS = [
    {
        id: 'followup-1',
        followUpDate: '3/01/2026 12:00 AM',
        assignedTo: 'Axel Hawker',
        status: 'Scheduled',
        createdAt: 100,
    },
    {
        id: 'followup-2',
        followUpDate: '3/08/2026 12:00 AM',
        assignedTo: 'Ben Chapman',
        status: 'Completed',
        createdAt: 300,
    },
    {
        id: 'followup-3',
        followUpDate: '3/15/2026 12:00 AM',
        assignedTo: 'Axel Hawker',
        status: 'Attempted',
        createdAt: 200,
    },
]

describe('FollowUpTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent(tracker) {
        // Actions stay stubbed: openExisting/refreshTracker would otherwise hit
        // the real API through the follow-up/tracker services.
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(FollowUpTab, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return {
            wrapper,
            trackerStore: useHospitalizationTracker(),
            followUpStore: useFollowUp(),
        }
    }

    it('renders every follow-up, including Completed/Attempted', () => {
        const { wrapper } = mountComponent({
            tracker_id: '#1',
            status: 'In Progress',
            followUps: FOLLOW_UPS,
        })
        expect(wrapper.findAll('tbody tr')).toHaveLength(3)
        expect(wrapper.text()).toContain('Completed')
        expect(wrapper.text()).toContain('Attempted')
    })

    it('orders follow-ups by createdAt descending', () => {
        const { wrapper } = mountComponent({
            tracker_id: '#1',
            status: 'In Progress',
            followUps: FOLLOW_UPS,
        })
        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].text()).toContain('Ben Chapman') // createdAt 300
        expect(rows[1].text()).toContain('3/15/2026') // createdAt 200
        expect(rows[2].text()).toContain('3/01/2026') // createdAt 100
    })

    it('clicking the calendar icon calls followUpStore.openExisting with a refresh callback', async () => {
        const { wrapper, trackerStore, followUpStore } = mountComponent({
            tracker_id: '#1',
            status: 'In Progress',
            followUps: FOLLOW_UPS,
        })

        await wrapper.find('.fa-calendar').trigger('click')

        expect(followUpStore.openExisting).toHaveBeenCalledWith(
            { trackerId: '#1', followUpId: 'followup-2' },
            expect.any(Function),
        )
        const [, onSaved] = followUpStore.openExisting.mock.calls[0]
        onSaved()
        expect(trackerStore.refreshTracker).toHaveBeenCalledOnce()
    })
})
