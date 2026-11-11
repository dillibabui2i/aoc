import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TrackerTitleBar from '@/modules/vbc/components/HospitalizationTracker/TrackerTitleBar.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { route } from 'ziggy-js'

vi.mock('ziggy-js', () => ({ route: vi.fn((name) => `/${name}`) }))

describe('TrackerTitleBar', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return mount(TrackerTitleBar, { global: { plugins: [pinia] } })
    }

    it('shows the plain title and "New" status before a tracker exists', () => {
        const wrapper = mountComponent()

        expect(wrapper.text()).toContain('Hospitalization Record')
        expect(wrapper.find('.tracker-title-bar__status').text()).toBe('New')
        expect(wrapper.find('.fa-user').exists()).toBe(false)
    })

    it('shows the patient/level title, status, and icons once a tracker is Open', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            patient_name: 'John Doe',
            level_label: 'Level II',
            status: 'Open',
            client_id: '99999-PDN',
        }
        await wrapper.vm.$nextTick()

        expect(wrapper.find('.tracker-title-bar__text span').text()).toBe(
            'Hospitalization Record \\ John Doe \\ Level II',
        )
        expect(wrapper.find('.tracker-title-bar__status').text()).toBe('Open')
        expect(wrapper.find('.fa-user').exists()).toBe(true)
        expect(wrapper.find('.fa-sticky-note-o').exists()).toBe(true)
    })

    it('clicking the Person icon opens the standard client profile modal', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            patient_id: 9001,
            patient_name: 'John Doe',
            level_label: 'Level II',
            status: 'Open',
            client_id: '99999-PDN',
        }
        await wrapper.vm.$nextTick()

        store.isModalOpen = true
        window.getClientDetails = vi.fn()
        const openSpy = vi.spyOn(window, 'open').mockImplementation(() => {})
        await wrapper.find('.fa-user').trigger('click')
        await flushPromises()

        expect(store.isModalOpen).toBe(false)
        expect(store.clientProfileConfirmOpen).toBe(false)
        expect(window.getClientDetails).toHaveBeenCalledWith(9001)
        expect(openSpy).not.toHaveBeenCalled()
        openSpy.mockRestore()
        delete window.getClientDetails
    })

    it('asks for confirmation instead of closing when there are unsaved changes', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = { patient_id: 9001, status: 'Open' }
        store.isModalOpen = true
        store.setUnsaved('currentInfo', true)
        await wrapper.vm.$nextTick()

        window.getClientDetails = vi.fn()
        await wrapper.find('.fa-user').trigger('click')
        await flushPromises()

        expect(store.clientProfileConfirmOpen).toBe(true)
        expect(store.isModalOpen).toBe(true)
        expect(window.getClientDetails).not.toHaveBeenCalled()
        delete window.getClientDetails
    })

    it('does nothing when the tracker has no patient_id', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            patient_name: 'John Doe',
            level_label: 'Level II',
            status: 'Open',
            client_id: '99999-PDN',
        }
        await wrapper.vm.$nextTick()

        window.getClientDetails = vi.fn()
        await wrapper.find('.fa-user').trigger('click')

        expect(window.getClientDetails).not.toHaveBeenCalled()
        delete window.getClientDetails
    })

    it.each(['Closed', 'Completed'])(
        'hides the Notes icon once the tracker is %s',
        async (status) => {
            const wrapper = mountComponent()
            const store = useHospitalizationTracker()
            store.wizardStep = 'open'
            store.tracker = {
                patient_name: 'John Doe',
                level_label: 'Level II',
                status,
                client_id: '99999-PDN',
            }
            await wrapper.vm.$nextTick()

            expect(wrapper.find('.fa-sticky-note-o').exists()).toBe(false)
            expect(wrapper.find('.fa-user').exists()).toBe(true)
        },
    )

    it('clicking the Notes icon opens the Create Notes modal', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            tracker_id: '#10087',
            patient_name: 'John Doe',
            level_label: 'Level II',
            status: 'Open',
            client_id: '99999-PDN',
        }
        await wrapper.vm.$nextTick()

        await wrapper.find('.fa-sticky-note-o').trigger('click')

        expect(store.openCreateNote).toHaveBeenCalled()
    })
})
