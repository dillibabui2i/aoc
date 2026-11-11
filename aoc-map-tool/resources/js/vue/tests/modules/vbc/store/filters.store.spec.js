import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useAllHospitalizationFiltersStore } from '@/modules/vbc/store/filters.store.js'
import { FiltersService } from '@/modules/vbc/services/filters.service.js'

vi.mock(
    '@/modules/vbc/services/filters.service.js',
    async (importOriginal) => ({
        ...(await importOriginal()),
        FiltersService: {
            getOptions: vi.fn(),
        },
    }),
)

describe('useAllHospitalizationFiltersStore', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('loads options via FiltersService', async () => {
        FiltersService.getOptions.mockResolvedValue({
            states: [
                { value: 'CA', text: 'California' },
                { value: 'NY', label: 'New York' },
            ],
            branches: [{ value: 7, text: 'Branch 7', state: 'CA' }],
        })

        const store = useAllHospitalizationFiltersStore()
        await store.fetchOptions()

        expect(store.options.states).toEqual([
            { value: 'CA', text: 'California', state: undefined },
            { value: 'NY', text: 'New York', state: undefined },
        ])
        expect(store.options.branches).toEqual([
            { value: 7, text: 'Branch 7', state: 'CA' },
        ])
        expect(store.options.regions).toEqual([])
        expect(store.loading).toBe(false)
        expect(store.error).toBe(null)
    })

    it('sets error when the request fails', async () => {
        FiltersService.getOptions.mockRejectedValue({
            response: { data: { message: 'Boom' } },
        })

        const store = useAllHospitalizationFiltersStore()
        await store.fetchOptions()

        expect(store.error).toBe('Boom')
        expect(store.loading).toBe(false)
    })

    it('updates a single filter value', () => {
        const store = useAllHospitalizationFiltersStore()
        store.setValue('state', 'CA')

        expect(store.values.state).toBe('CA')
    })
})
