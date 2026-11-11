import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import HospitalizationBucketSection from '@/modules/vbc/sections/Dashboard/HospitalizationBucketSection.vue'
import { useHospitalization } from '@/modules/vbc/store/Dashboard/hospitalization.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

describe('HospitalizationBucketSection.vue', () => {
    let pinia

    beforeEach(() => {
        // Actions stay stubbed: fetchAll and the tracker store's loaders would
        // otherwise hit the API.
        pinia = createTestingPinia({ createSpy: vi.fn })
    })

    async function mountComponent() {
        const wrapper = mount(HospitalizationBucketSection, {
            global: {
                plugins: [pinia],
                // The filters and both modals load their own options from the
                // API on mount; irrelevant to the Notes icon behavior under test.
                stubs: {
                    HospitalizationBucketFilters: true,
                    HospitalizationTrackerModal: true,
                    CreateNoteModal: true,
                },
            },
        })
        const store = useHospitalization()
        store.loading = false
        store.list = [
            { id: 1, tracker_id: 12345, status: 'Open' },
            { id: 2, tracker_id: 23432, status: 'Discharged' },
            { id: 3, tracker_id: 99999, status: 'Closed' },
        ]
        await wrapper.vm.$nextTick()
        return wrapper
    }

    it('shows the Notes icon for rows that are not Closed or Completed', async () => {
        const wrapper = await mountComponent()

        const rows = wrapper.findAll('tbody tr')
        expect(rows[0].find('a[title="Notes"]').exists()).toBe(true)
        expect(rows[1].find('a[title="Notes"]').exists()).toBe(true)
    })

    it('hides the Notes icon for a Closed row', async () => {
        const wrapper = await mountComponent()

        const rows = wrapper.findAll('tbody tr')
        expect(rows[2].find('a[title="Notes"]').exists()).toBe(false)
    })

    it('clicking the Notes icon opens the Create Notes modal for that tracker', async () => {
        const wrapper = await mountComponent()
        const trackerStore = useHospitalizationTracker()

        const rows = wrapper.findAll('tbody tr')
        await rows[0].find('a[title="Notes"]').trigger('click')

        expect(trackerStore.openCreateNoteForTracker).toHaveBeenCalledWith(
            12345,
        )
    })

    it('mounts CreateNoteModal', async () => {
        const wrapper = await mountComponent()

        expect(
            wrapper.findComponent({ name: 'CreateNoteModal' }).exists(),
        ).toBe(true)
    })
})
