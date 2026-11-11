import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdTrackerInfoGrid from '@/modules/vbc/components/EdVisitTracker/EdTrackerInfoGrid.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

// PD-5636: the saved assignee must stay visible and selectable even when the live assignee options
// are empty (not loaded yet / failed) or no longer include them.
describe('EdTrackerInfoGrid Assigned To', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    async function mountOpen(tracker, assignees) {
        const wrapper = mount(EdTrackerInfoGrid, {
            global: { plugins: [pinia] },
        })
        const store = useEdVisitTracker()
        store.assignees = assignees
        store.wizardStep = 'open'
        store.tracker = tracker
        await wrapper.vm.$nextTick()

        return wrapper
    }

    function assignedToSelect(wrapper) {
        return wrapper
            .findAll('select')
            .find((s) => s.text().includes('Jane Doe'))
    }

    it('shows the saved assignee as selected when the options list is empty', async () => {
        const wrapper = await mountOpen(
            {
                status: 'In Progress',
                assigned_to_id: 7,
                assigned_to_name: 'Jane Doe',
            },
            [],
        )

        const select = assignedToSelect(wrapper)
        expect(select.element.disabled).toBe(false)
        expect(select.element.value).toBe('7')
    })

    it('does not duplicate the assignee when they are in the options list', async () => {
        const wrapper = await mountOpen(
            {
                status: 'In Progress',
                assigned_to_id: 7,
                assigned_to_name: 'Jane Doe',
            },
            [
                { value: 7, label: 'Jane Doe' },
                { value: 8, label: 'John Roe' },
            ],
        )

        const matches = assignedToSelect(wrapper)
            .findAll('option')
            .filter((o) => o.text() === 'Jane Doe')
        expect(matches).toHaveLength(1)
    })

    it('uses the read-only display for a Completed tracker', async () => {
        const wrapper = await mountOpen(
            {
                status: 'Completed',
                assigned_to_id: 7,
                assigned_to_name: 'Jane Doe',
            },
            [],
        )

        expect(assignedToSelect(wrapper)).toBeUndefined()
    })
})
