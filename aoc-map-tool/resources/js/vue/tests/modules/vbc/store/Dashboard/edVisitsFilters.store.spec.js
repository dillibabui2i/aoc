import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdVisitsFiltersStore } from '@/modules/vbc/store/Dashboard/edVisitsFilters.store.js'
import { FiltersService } from '@/modules/vbc/services/filters.service.js'

vi.mock('@/modules/vbc/services/filters.service.js', () => ({
    FiltersService: { getOptions: vi.fn() },
    toOptionList: (items) =>
        (items ?? []).map((item) => ({ value: item.value, text: item.text })),
}))

describe('useEdVisitsFiltersStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        FiltersService.getOptions.mockResolvedValue({
            hospitalizationStatuses: [
                { value: 'IN_PROGRESS', text: 'In Progress' },
                { value: 'READY_FOR_REVIEW', text: 'Ready for Review' },
                { value: 'READY_FOR_NEXT_STEPS', text: 'Ready for Next Steps' },
            ],
        })
    })

    it('requests the ED_VISITS bucket options', async () => {
        const store = useEdVisitsFiltersStore()

        await store.fetchOptions()

        expect(FiltersService.getOptions).toHaveBeenCalledWith('ED_VISITS')
    })

    it('uses the statuses returned by the backend as-is', async () => {
        const store = useEdVisitsFiltersStore()

        await store.fetchOptions()

        expect(store.options.statuses).toEqual([
            { value: 'IN_PROGRESS', text: 'In Progress' },
            { value: 'READY_FOR_REVIEW', text: 'Ready for Review' },
            { value: 'READY_FOR_NEXT_STEPS', text: 'Ready for Next Steps' },
        ])
    })

    it('reset() clears the status selection', () => {
        const store = useEdVisitsFiltersStore()
        store.values.status = ['READY_FOR_REVIEW']

        store.reset()

        expect(store.values.status).toEqual([])
    })
})
