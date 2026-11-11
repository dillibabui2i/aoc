import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CommunicationBucketSection from '@/modules/vbc/sections/Dashboard/CommunicationBucketSection.vue'
import { useCommunication } from '@/modules/vbc/store/Dashboard/communication.store.js'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'

describe('CommunicationBucketSection.vue', () => {
    let pinia

    beforeEach(() => {
        // Actions stay stubbed: fetchAll / openExisting would otherwise hit the API.
        pinia = createTestingPinia({ createSpy: vi.fn })
    })

    async function mountComponent() {
        const wrapper = mount(CommunicationBucketSection, {
            global: {
                plugins: [pinia],
                // CommunicationFilters and FollowUpModal load their own options
                // from the API on mount; irrelevant to the row actions under test.
                stubs: { CommunicationFilters: true, FollowUpModal: true },
            },
        })
        const store = useCommunication()
        store.loading = false
        store.list = [
            {
                id: 'followup-1',
                patient_name: 'John Doe',
                hospitalization_tracker_id: '#12346',
            },
        ]
        await wrapper.vm.$nextTick()
        return wrapper
    }

    it('fetches the list on mount', async () => {
        await mountComponent()

        expect(useCommunication().fetchAll).toHaveBeenCalledOnce()
    })

    it('always shows the calendar icon (no client-side permission gating)', async () => {
        const wrapper = await mountComponent()

        expect(wrapper.findAll('a[title="Open Follow-up"]')).toHaveLength(1)
    })

    it('clicking the calendar icon calls followUpStore.openExisting with the row ids', async () => {
        const wrapper = await mountComponent()
        const followUpStore = useFollowUp()

        await wrapper.find('a[title="Open Follow-up"]').trigger('click')

        expect(followUpStore.openExisting).toHaveBeenCalledWith(
            { trackerId: '#12346', followUpId: 'followup-1' },
            expect.any(Function),
        )
        // The refresh callback reloads the bucket after a submit.
        const [, onSaved] = followUpStore.openExisting.mock.calls[0]
        useCommunication().fetchAll.mockClear()
        onSaved()
        expect(useCommunication().fetchAll).toHaveBeenCalledOnce()
    })

    it('mounts FollowUpModal', async () => {
        const wrapper = await mountComponent()

        expect(wrapper.findComponent({ name: 'FollowUpModal' }).exists()).toBe(
            true,
        )
    })
})
