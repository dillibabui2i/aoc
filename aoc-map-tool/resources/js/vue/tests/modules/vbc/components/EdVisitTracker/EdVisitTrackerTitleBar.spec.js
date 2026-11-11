import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdVisitTrackerTitleBar from '@/modules/vbc/components/EdVisitTracker/EdVisitTrackerTitleBar.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

describe('EdVisitTrackerTitleBar', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
        window.getClientDetails = vi.fn()
    })

    afterEach(() => {
        delete window.getClientDetails
    })

    async function mountOpen(tracker) {
        const wrapper = mount(EdVisitTrackerTitleBar, {
            global: { plugins: [pinia] },
        })
        const store = useEdVisitTracker()
        store.wizardStep = 'open'
        store.tracker = {
            patient_name: 'John Doe',
            level_label: 'Level II',
            status: 'Open',
            ...tracker,
        }
        await wrapper.vm.$nextTick()
        return wrapper
    }

    it('clicking the Person icon opens the standard client profile modal', async () => {
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => {})
        const wrapper = await mountOpen({ patient_id: 9001 })

        const store = useEdVisitTracker()
        store.isModalOpen = true

        await wrapper.find('.fa-user').trigger('click')
        await flushPromises()

        expect(store.isModalOpen).toBe(false)
        expect(store.clientProfileConfirmOpen).toBe(false)
        expect(window.getClientDetails).toHaveBeenCalledWith(9001)
        expect(openSpy).not.toHaveBeenCalled()
        openSpy.mockRestore()
    })

    it('asks for confirmation instead of closing when there are unsaved changes', async () => {
        const wrapper = await mountOpen({ patient_id: 9001 })
        const store = useEdVisitTracker()
        store.isModalOpen = true
        store.setUnsaved('currentInfo', true)

        await wrapper.find('.fa-user').trigger('click')
        await flushPromises()

        expect(store.clientProfileConfirmOpen).toBe(true)
        expect(store.isModalOpen).toBe(true)
        expect(window.getClientDetails).not.toHaveBeenCalled()
    })

    it('does nothing when the tracker has no patient_id', async () => {
        const wrapper = await mountOpen({})

        await wrapper.find('.fa-user').trigger('click')

        expect(window.getClientDetails).not.toHaveBeenCalled()
    })
})
