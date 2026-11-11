import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ClientSearchAutocomplete from '@/modules/vbc/components/HospitalizationTracker/ClientSearchAutocomplete.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// patient_id is what the component keys on and what selectClient() sends —
// client_id is only the human-facing account number shown beside the name.
const RESULTS = [
    { patient_id: 1, client_id: '99999-PDN', patient_name: 'John Doe' },
    { patient_id: 2, client_id: '91299-PDN', patient_name: 'John Doe' },
]

describe('ClientSearchAutocomplete', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return mount(ClientSearchAutocomplete, {
            global: { plugins: [pinia] },
        })
    }

    it('shows the input while searching and calls store.searchClients on input', async () => {
        vi.useFakeTimers()
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()

        await wrapper.find('input').setValue('John')
        vi.advanceTimersByTime(300)

        expect(store.searchClients).toHaveBeenCalledWith('John')
        vi.useRealTimers()
    })

    it('renders same-name clients as distinct, ID-labeled entries', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.clientSearchResults = RESULTS
        store.clientSearchPerformed = true
        await wrapper.vm.$nextTick()

        const items = wrapper.findAll('.client-search__result')
        expect(items).toHaveLength(2)
        expect(items[0].text()).toBe('John Doe, ID 99999-PDN')
        expect(items[1].text()).toBe('John Doe, ID 91299-PDN')
    })

    it('selecting a result resolves the correct clientId via store.selectClient', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.clientSearchResults = RESULTS
        store.clientSearchPerformed = true
        await wrapper.vm.$nextTick()

        await wrapper.findAll('.client-search__result')[1].trigger('click')

        expect(store.selectClient).toHaveBeenCalledWith(RESULTS[1])
    })

    it('shows a disabled read-only display once a client is selected (not the "search" step)', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'confirmed'
        store.selectedClient = {
            client_id: '12345-PDN',
            patient_name: 'John Taylor',
        }
        await wrapper.vm.$nextTick()

        const input = wrapper.find('input')
        expect(input.element.disabled).toBe(true)
        expect(input.element.value).toBe('John Taylor, ID 12345-PDN')
    })

    it('says so when a search matches nobody, without offering a selectable row', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.clientSearchResults = []
        store.clientSearchPerformed = true
        await wrapper.vm.$nextTick()

        const items = wrapper.findAll('.client-search__result')
        expect(items).toHaveLength(1)
        expect(items[0].text()).toBe('( no clients found )')

        await items[0].trigger('click')
        expect(store.selectClient).not.toHaveBeenCalled()
    })

    it('shows nothing before any search has been answered', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()
        store.clientSearchResults = []
        store.clientSearchPerformed = false
        await wrapper.vm.$nextTick()

        expect(wrapper.find('.client-search__results').exists()).toBe(false)
    })
})
