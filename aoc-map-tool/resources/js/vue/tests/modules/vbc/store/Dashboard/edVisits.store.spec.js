import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdVisits } from '@/modules/vbc/store/Dashboard/edVisits.store.js'
import { EdVisitsService } from '@/modules/vbc/services/Dashboard/edVisits.service.js'

vi.mock('@/modules/vbc/services/Dashboard/edVisits.service.js', () => ({
    EdVisitsService: {
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

describe('useEdVisits', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        EdVisitsService.getAll.mockResolvedValue(mockResponse())
    })

    it('always narrows the request to ED encounters', async () => {
        await useEdVisits().fetchAll()

        expect(EdVisitsService.getAll).toHaveBeenCalledWith(
            expect.objectContaining({ encounter_types: ['ED'] }),
        )
    })

    it('maps the filter bar values onto the endpoint params', async () => {
        const store = useEdVisits()

        store.setFilters({
            me_mode: true,
            state: ['TX'],
            branch: [3],
            region: [4],
            assignee: [5],
            status: ['IN_PROGRESS'],
            date_from: '03/01/2026',
            date_to: '03/31/2026',
            search: 'Doe',
        })
        await vi.waitFor(() =>
            expect(EdVisitsService.getAll).toHaveBeenCalled(),
        )

        expect(EdVisitsService.getAll).toHaveBeenCalledWith({
            page: 1,
            per_page: 10,
            sort_by: 'admission_date',
            sort_dir: 'desc',
            me_mode: true,
            state: ['TX'],
            branch_ids: [3],
            region_ids: [4],
            assignee_ids: [5],
            search: 'Doe',
            encounter_types: ['ED'],
            statuses: ['IN_PROGRESS'],
            admission_date_from: '03/01/2026',
            admission_date_to: '03/31/2026',
        })
    })

    it('keeps the ED narrowing after Clear All', async () => {
        const store = useEdVisits()

        store.setFilters({ state: ['TX'] })
        store.resetFilters()
        await vi.waitFor(() =>
            expect(EdVisitsService.getAll).toHaveBeenCalledTimes(2),
        )

        expect(EdVisitsService.getAll).toHaveBeenLastCalledWith({
            page: 1,
            per_page: 10,
            sort_by: 'admission_date',
            sort_dir: 'desc',
            encounter_types: ['ED'],
        })
    })

    it('stores the rows and pagination meta', async () => {
        EdVisitsService.getAll.mockResolvedValue(
            mockResponse({
                data: [{ id: 1 }],
                total: 12,
                current_page: 2,
                per_page: 10,
            }),
        )
        const store = useEdVisits()

        await store.fetchAll()

        expect(store.list).toEqual([{ id: 1 }])
        expect(store.total).toBe(12)
        expect(store.page).toBe(2)
        expect(store.loading).toBe(false)
    })

    it('exposes the backend error message', async () => {
        EdVisitsService.getAll.mockRejectedValue({
            response: { data: { message: 'Forbidden' } },
        })
        const store = useEdVisits()

        await store.fetchAll()

        expect(store.error).toBe('Forbidden')
    })
})
