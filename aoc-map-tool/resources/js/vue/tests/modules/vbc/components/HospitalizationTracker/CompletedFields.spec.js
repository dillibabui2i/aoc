import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CompletedFields from '@/modules/vbc/components/HospitalizationTracker/CompletedFields.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const ASSIGNEES = [
    { value: 5, label: 'Anna Lopatnova' },
    { value: 7, label: 'Maria Ivanova' },
]

describe('CompletedFields', () => {
    // The "Completed by" default is read from the store at component
    // setup, so the tracker must be in place via `initialState` before
    // mount, not assigned afterward.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                hospitalizationTracker: { tracker, assignees: ASSIGNEES },
            },
        })
        const wrapper = mount(CompletedFields, {
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('defaults "Completed by" to the current assignee', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            assigned_to_id: 5,
        })
        expect(wrapper.find('select').element.value).toBe('5')
    })

    it('prevents submission and marks "Completed by" as required when empty', async () => {
        const { wrapper, store } = mountComponent({ tracker_id: 1 })

        await wrapper.find('button').trigger('click')

        expect(store.submitCompleted).not.toHaveBeenCalled()
        expect(wrapper.find('.ui-select-wrap--error').exists()).toBe(true)
        expect(wrapper.text()).toContain('Completed by is required.')
        // Completion notes are optional.
        expect(wrapper.find('.ui-textarea-wrap--error').exists()).toBe(false)
    })

    it('submits the selected completer and notes when valid', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            assigned_to_id: 5,
        })

        await wrapper
            .find('textarea')
            .setValue(
                'This hospitalization can be completed, all next steps made.',
            )
        await wrapper.find('button').trigger('click')

        expect(store.submitCompleted).toHaveBeenCalledWith({
            completedBy: 5,
            notes: 'This hospitalization can be completed, all next steps made.',
        })
    })
})
