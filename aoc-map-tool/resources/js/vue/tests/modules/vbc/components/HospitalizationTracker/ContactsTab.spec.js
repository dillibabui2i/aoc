import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ContactsTab from '@/modules/vbc/components/HospitalizationTracker/ContactsTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// Opening the real ContactDetailsModal lazily loads its Relationship options
// through the service — mocked so no real HTTP happens.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                getContactTypes: vi.fn().mockResolvedValue([]),
            },
        }
    },
)

const CONTACTS = [
    {
        id: 'contact-1',
        contactName: 'Pamela Hopkins',
        relationship: 'Step Mother',
        phoneNumber: '(555) 113-4212',
        email: 'not@for.you',
    },
    {
        id: 'contact-2',
        contactName: 'Earl Jones',
        relationship: 'Father',
        phoneNumber: '(555) 123-4432',
        email: 'not@for.you',
    },
]

describe('ContactsTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(ContactsTab, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('renders a row per tracker contact', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            contacts: CONTACTS,
        })
        expect(wrapper.findAll('tbody tr')).toHaveLength(2)
        expect(wrapper.text()).toContain('Pamela Hopkins')
        expect(wrapper.text()).toContain('Earl Jones')
    })

    it('shows Add Contact while the tracker is In Progress', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            contacts: [],
        })
        expect(wrapper.text()).toContain('Add Contact')
    })

    it('hides Add Contact once the tracker leaves In Progress', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'Discharged',
            contacts: [],
        })
        expect(wrapper.text()).not.toContain('Add Contact')
    })

    it('clicking Add Contact calls store.openAddContact', async () => {
        const { wrapper, store } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            contacts: [],
        })
        await wrapper.find('.contacts-tab__add').trigger('click')
        expect(store.openAddContact).toHaveBeenCalledOnce()
    })

    it('clicking the calendar icon calls store.openEditContact with the row', async () => {
        const { wrapper, store } = mountComponent({
            trackerId: '#1',
            status: 'In Progress',
            contacts: CONTACTS,
        })
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditContact).toHaveBeenCalledWith(CONTACTS[0])
    })

    it('shows the calendar icon action even when read-only', () => {
        const { wrapper } = mountComponent({
            trackerId: '#1',
            status: 'Discharged',
            contacts: CONTACTS,
        })
        expect(wrapper.find('.fa-calendar').exists()).toBe(true)
    })
})
