import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import AllHospitalizationSection from '@/modules/vbc/sections/AllHospitalizationSection.vue'
import { useAllHospitalization } from '@/modules/vbc/store/allHospitalization.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// The tracker store loads its Assigned To options when the section mounts.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                getAssignees: vi.fn().mockResolvedValue([]),
            },
        }
    },
)

vi.mock('@/modules/vbc/composables/usePermissions.js', () => ({
    useHasPermission: vi.fn(() => true),
}))

describe('AllHospitalizationSection.vue', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return shallowMount(AllHospitalizationSection, {
            global: { plugins: [pinia] },
        })
    }

    it('fetches the list on mount', () => {
        mountComponent()
        const store = useAllHospitalization()

        expect(store.fetchAll).toHaveBeenCalledOnce()
    })

    it('applies filters via the store when the filters component emits search', async () => {
        const wrapper = mountComponent()
        const store = useAllHospitalization()

        await wrapper
            .findComponent({ name: 'HospitalizationFilters' })
            .vm.$emit('search', { state: 'CA' })

        expect(store.setFilters).toHaveBeenCalledWith({ state: 'CA' })
    })

    it('resets filters via the store when the filters component emits clear', async () => {
        const wrapper = mountComponent()
        const store = useAllHospitalization()

        await wrapper
            .findComponent({ name: 'HospitalizationFilters' })
            .vm.$emit('clear')

        expect(store.resetFilters).toHaveBeenCalledOnce()
    })

    it('shows the store error instead of the table when present', async () => {
        const wrapper = mountComponent()
        const store = useAllHospitalization()
        store.error = 'Failed to load data'
        await wrapper.vm.$nextTick()

        expect(wrapper.text()).toContain('Failed to load data')
        expect(wrapper.findComponent({ name: 'UiTable' }).exists()).toBe(false)
    })

    it('shows the "+" tracker-creation action and opens the modal on click (permission stub is always granted)', async () => {
        const wrapper = mountComponent()
        const trackerStore = useHospitalizationTracker()

        const createLink = wrapper.find('a[title="New Hospitalization Record"]')
        expect(createLink.exists()).toBe(true)

        await createLink.trigger('click')

        expect(trackerStore.openCreateModal).toHaveBeenCalledOnce()
        expect(
            wrapper
                .findComponent({ name: 'HospitalizationTrackerModal' })
                .exists(),
        ).toBe(true)
    })
})
