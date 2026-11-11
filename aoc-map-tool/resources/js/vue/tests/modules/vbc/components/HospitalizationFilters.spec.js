import { describe, it, expect, vi, beforeEach } from 'vitest'
import { shallowMount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import HospitalizationFilters from '@/modules/vbc/components/HospitalizationFilters.vue'
import { useAllHospitalizationFiltersStore } from '@/modules/vbc/store/filters.store.js'

describe('HospitalizationFilters.vue', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return shallowMount(HospitalizationFilters, {
            global: { plugins: [pinia] },
        })
    }

    it('fetches filter options on mount', () => {
        mountComponent()
        const store = useAllHospitalizationFiltersStore()

        expect(store.fetchOptions).toHaveBeenCalledOnce()
    })

    it('emits search with current values on submit', async () => {
        const wrapper = mountComponent()
        const store = useAllHospitalizationFiltersStore()
        store.values.state = 'CA'

        await wrapper.vm.getFilteredData()

        expect(wrapper.emitted('search')).toEqual([[{ ...store.values }]])
    })

    it('resets the store and emits clear', async () => {
        const wrapper = mountComponent()
        const store = useAllHospitalizationFiltersStore()

        await wrapper.vm.onClear()

        expect(store.reset).toHaveBeenCalledOnce()
        expect(wrapper.emitted('clear')).toHaveLength(1)
    })
})
