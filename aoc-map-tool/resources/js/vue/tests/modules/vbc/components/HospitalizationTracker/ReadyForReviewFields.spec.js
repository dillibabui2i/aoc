import { describe, it, expect, vi } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ReadyForReviewFields from '@/modules/vbc/components/HospitalizationTracker/ReadyForReviewFields.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const ASSIGNEES = [
    { value: 5, label: 'Anna Lopatnova' },
    { value: 7, label: 'Maria Ivanova' },
]

describe('ReadyForReviewFields', () => {
    function mountComponent() {
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: {
                hospitalizationTracker: {
                    tracker: { tracker_id: 1 },
                    assignees: ASSIGNEES,
                },
            },
        })
        const wrapper = mount(ReadyForReviewFields, {
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('prevents submission and marks "Review to be made by" as required when empty', async () => {
        const { wrapper, store } = mountComponent()

        await wrapper.find('button').trigger('click')

        expect(store.submitReadyForReview).not.toHaveBeenCalled()
        expect(wrapper.find('.ui-select-wrap--error').exists()).toBe(true)
        expect(wrapper.text()).toContain('Review to be made by is required.')
        // Ready for Review notes are optional.
        expect(wrapper.find('.ui-textarea-wrap--error').exists()).toBe(false)
    })

    it('submits the reviewer and notes when valid', async () => {
        const { wrapper, store } = mountComponent()

        await wrapper.find('select').setValue('7')
        await wrapper
            .find('textarea')
            .setValue(
                'Julie, this hospitalization record is ready for your review.',
            )
        await wrapper.find('button').trigger('click')

        expect(store.submitReadyForReview).toHaveBeenCalledWith({
            reviewer: '7',
            notes: 'Julie, this hospitalization record is ready for your review.',
        })
    })
})
