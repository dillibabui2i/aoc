import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ReadyForNextStepsFields from '@/modules/vbc/components/HospitalizationTracker/ReadyForNextStepsFields.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const ASSIGNEES = [
    { value: 5, label: 'Anna Lopatnova' },
    { value: 7, label: 'Maria Ivanova' },
]

describe('ReadyForNextStepsFields', () => {
    // The "Next Steps to be made by" default is read from the store at
    // component setup, so the tracker must be in place via `initialState`
    // before mount, not assigned afterward.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                hospitalizationTracker: { tracker, assignees: ASSIGNEES },
            },
        })
        const wrapper = mount(ReadyForNextStepsFields, {
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('defaults "Next Steps to be made by" to Who Sent to Review', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            sent_to_review_by_id: 5,
        })
        expect(wrapper.find('select').element.value).toBe('5')
    })

    it('prevents submission and marks "Next Steps to be made by" as required when empty', async () => {
        const { wrapper, store } = mountComponent({ tracker_id: 1 })

        await wrapper.find('button').trigger('click')

        expect(store.submitReadyForNextSteps).not.toHaveBeenCalled()
        expect(wrapper.find('.ui-select-wrap--error').exists()).toBe(true)
        expect(wrapper.text()).toContain(
            'Next Steps to be made by is required.',
        )
        // Ready for Next Steps notes are optional.
        expect(wrapper.find('.ui-textarea-wrap--error').exists()).toBe(false)
    })

    it('submits the selected user and notes when valid', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            sent_to_review_by_id: 5,
        })

        await wrapper
            .find('textarea')
            .setValue(
                'Emma, this hospitalization record is ready for the next steps.',
            )
        await wrapper.find('button').trigger('click')

        expect(store.submitReadyForNextSteps).toHaveBeenCalledWith({
            nextStepsBy: 5,
            notes: 'Emma, this hospitalization record is ready for the next steps.',
        })
    })
})
