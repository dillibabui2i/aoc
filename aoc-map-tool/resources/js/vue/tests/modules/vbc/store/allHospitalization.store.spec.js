import { describe, it, expect, vi, beforeEach } from 'vitest'
import { flushPromises } from '@vue/test-utils'
import { setActivePinia, createPinia } from 'pinia'
import { useAllHospitalization } from '@/modules/vbc/store/allHospitalization.store.js'
import { AllHospitalizationService } from '@/modules/vbc/services/allHospitalization.service.js'

vi.mock('@/modules/vbc/services/allHospitalization.service.js', () => ({
    AllHospitalizationService: {
        getAll: vi.fn(),
    },
}))

function mockResponse({
    data = [],
    total = 0,
    current_page = 1,
    per_page = 10,
} = {}) {
    return { data, meta: { total, current_page, per_page } }
}

describe('useAllHospitalization', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('fetches the list and normalizes scalar meta values', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(
            mockResponse({
                data: [{ id: 1 }],
                total: 42,
                current_page: 2,
                per_page: 20,
            }),
        )

        const store = useAllHospitalization()
        await store.fetchAll()

        expect(store.list).toEqual([{ id: 1 }])
        expect(store.total).toBe(42)
        expect(store.page).toBe(2)
        expect(store.perPage).toBe(20)
        expect(store.loading).toBe(false)
        expect(store.error).toBe(null)
    })

    it('strips empty/null/blank-array filter values from the query', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(mockResponse())

        const store = useAllHospitalization()
        store.setFilters({
            state: 'CA',
            region: '',
            assignees: [],
            search: null,
        })
        await store.fetchAll()

        const query = AllHospitalizationService.getAll.mock.calls.at(-1)[0]
        expect(query).toEqual({
            page: 1,
            per_page: 10,
            sort_dir: 'asc',
            encounter_types: ['IP'],
            state: 'CA',
        })
    })

    it('sets an error message when the request fails', async () => {
        AllHospitalizationService.getAll.mockRejectedValue({
            response: { data: { message: 'Boom' } },
        })

        const store = useAllHospitalization()
        await store.fetchAll()

        expect(store.error).toBe('Boom')
        expect(store.loading).toBe(false)
    })

    it('falls back to a generic error message when none is provided', async () => {
        AllHospitalizationService.getAll.mockRejectedValue(
            new Error('network down'),
        )

        const store = useAllHospitalization()
        await store.fetchAll()

        expect(store.error).toBe('Failed to load data')
    })

    it('setPage requests the given page and refetches', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(
            mockResponse({ current_page: 3 }),
        )
        const store = useAllHospitalization()

        store.setPage(3)

        expect(store.page).toBe(3)
        await flushPromises()

        const query = AllHospitalizationService.getAll.mock.calls.at(-1)[0]
        expect(query.page).toBe(3)
        expect(AllHospitalizationService.getAll).toHaveBeenCalledTimes(1)
    })

    it('setPerPage resets page to 1 and refetches', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(
            mockResponse({ current_page: 1, per_page: 25 }),
        )
        const store = useAllHospitalization()
        store.page = 5

        store.setPerPage(25)

        expect(store.page).toBe(1)
        await flushPromises()

        const query = AllHospitalizationService.getAll.mock.calls.at(-1)[0]
        expect(query.page).toBe(1)
        expect(query.per_page).toBe(25)
        expect(store.perPage).toBe(25)
    })

    it('setSort updates sort state, resets page and refetches', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(mockResponse())
        const store = useAllHospitalization()
        store.page = 4

        store.setSort('patient_name', 'desc')
        await flushPromises()

        expect(store.sortBy).toBe('patient_name')
        expect(store.sortDir).toBe('desc')
        expect(store.page).toBe(1)
    })

    it('resetFilters clears active filters, resets page and refetches', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(mockResponse())
        const store = useAllHospitalization()
        store.setFilters({ state: 'CA' })
        await flushPromises()
        vi.clearAllMocks()
        AllHospitalizationService.getAll.mockResolvedValue(mockResponse())

        store.resetFilters()
        await flushPromises()

        const query = AllHospitalizationService.getAll.mock.calls.at(-1)[0]
        expect(query).not.toHaveProperty('state')
        expect(store.page).toBe(1)
    })

    it('totalPages is derived from total and perPage', async () => {
        AllHospitalizationService.getAll.mockResolvedValue(
            mockResponse({ total: 25, per_page: 10 }),
        )
        const store = useAllHospitalization()
        await store.fetchAll()

        expect(store.totalPages).toBe(3)
    })

    it('totalPages defaults to 1 when total is 0', () => {
        const store = useAllHospitalization()

        expect(store.totalPages).toBe(1)
    })
})
