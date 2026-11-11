import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import TrackerInfoGrid from '@/modules/vbc/components/HospitalizationTracker/TrackerInfoGrid.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// A client profile as returned by getClient(), with the two required header
// fields (Assigned To, Level) already answered.
const CLIENT = {
    patient_id: 501,
    client_id: '12345-PDN',
    patient_name: 'John Taylor',
    date_of_birth: '02/14/1958',
    backup_g_tube: true,
    backup_trach: true,
    assigned_to_id: 1,
    level: 'LEVEL_I',
}

// "Assigned To" options come from the store (loaded from the API).
const ASSIGNEES = [
    { value: 1, label: 'Anna Lopatnova' },
    { value: 2, label: 'Maria Ivanova' },
]

describe('TrackerInfoGrid', () => {
    let pinia

    beforeEach(() => {
        // Actions stay stubbed: submitTracker/autoSaveField would hit the API.
        pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { assignees: ASSIGNEES } },
        })
    })

    function mountComponent() {
        return mount(TrackerInfoGrid, { global: { plugins: [pinia] } })
    }

    function assignedToSelect(wrapper) {
        return wrapper
            .findAll('select')
            .find((s) => s.text().includes('Maria Ivanova'))
    }

    it('disables every field except Client Name in the "search" step', () => {
        const wrapper = mountComponent()

        // All non-client-search inputs should render disabled.
        wrapper.findAll('input:not([type=checkbox])').forEach((input) => {
            if (input.element.closest('.client-search')) return
            expect(input.element.disabled).toBe(true)
        })
        expect(wrapper.find('.info-grid__submit').exists()).toBe(false)
    })

    it('shows a Submit button once a client is confirmed with no conflict', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'confirmed'
        store.selectedClient = CLIENT
        await wrapper.vm.$nextTick()

        expect(wrapper.find('.info-grid__submit').exists()).toBe(true)
    })

    it('submits the staged custom-field draft together with the selected client', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'confirmed'
        store.selectedClient = CLIENT
        await wrapper.vm.$nextTick()

        await wrapper.find('.info-grid__submit button').trigger('click')

        expect(store.submitTracker).toHaveBeenCalledOnce()
        const overrides = store.submitTracker.mock.calls[0][0]
        expect(overrides.backup_g_tube).toBe(true)
        expect(overrides.backup_trach).toBe(true)
        expect(overrides.assigned_to_id).toBe(1)
        expect(overrides.level).toBe('LEVEL_I')
    })

    it('blocks Submit and names each empty required field', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'confirmed'
        store.selectedClient = { ...CLIENT, assigned_to_id: null, level: '' }
        await wrapper.vm.$nextTick()

        await wrapper.find('.info-grid__submit button').trigger('click')

        expect(store.submitTracker).not.toHaveBeenCalled()
        expect(wrapper.text()).toContain('Assigned To is required.')
        expect(wrapper.text()).toContain('Level is required.')
    })

    it('auto-saves an editable field once the tracker is Open', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = { tracker_id: 11123, status: 'Open', level: 'LEVEL_I' }
        await wrapper.vm.$nextTick()

        const levelSelect = wrapper
            .findAll('select')
            .find((s) => s.text().includes('Level II'))
        await levelSelect.setValue('LEVEL_II')

        expect(store.autoSaveField).toHaveBeenCalledWith('level', 'LEVEL_II')
    })

    it('keeps Assigned To enabled once the tracker is In Progress', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            tracker_id: 11123,
            status: 'In Progress',
            assigned_to_id: 1,
        }
        await wrapper.vm.$nextTick()

        expect(assignedToSelect(wrapper).element.disabled).toBe(false)
    })

    it('disables every editable field once the tracker is Closed', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            tracker_id: 11123,
            status: 'Closed',
            assigned_to_id: 1,
            level: 'LEVEL_I',
        }
        await wrapper.vm.$nextTick()

        wrapper.findAll('input:not([type=checkbox])').forEach((input) => {
            expect(input.element.disabled).toBe(true)
        })
        wrapper
            .findAll('input[type=checkbox]')
            .forEach((toggle) => expect(toggle.element.disabled).toBe(true))
    })

    it('stays disabled when a Closed tracker is loaded directly (reopen)', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = { tracker_id: 10087, status: 'Closed' }
        await wrapper.vm.$nextTick()

        wrapper.findAll('input:not([type=checkbox])').forEach((input) => {
            expect(input.element.disabled).toBe(true)
        })
    })

    it('disables Assigned To and custom fields once the tracker is Completed', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            tracker_id: 11123,
            status: 'Completed',
            assigned_to_id: 1,
        }
        await wrapper.vm.$nextTick()

        wrapper.findAll('input:not([type=checkbox])').forEach((input) => {
            expect(input.element.disabled).toBe(true)
        })
    })

    it('re-syncs the Assigned To dropdown when the tracker is reassigned programmatically', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'open'
        store.tracker = {
            tracker_id: 11123,
            status: 'Ready for Review',
            assigned_to_id: 1,
        }
        await wrapper.vm.$nextTick()

        const select = assignedToSelect(wrapper)
        expect(select.element.value).toBe('1')

        store.tracker = { ...store.tracker, assigned_to_id: 2 }
        await wrapper.vm.$nextTick()

        expect(select.element.value).toBe('2')
    })
})
