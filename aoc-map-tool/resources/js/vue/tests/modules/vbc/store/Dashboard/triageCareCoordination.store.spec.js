import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useTriageCareCoordination } from '@/modules/vbc/store/Dashboard/triageCareCoordination.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'

vi.mock(
    '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js',
    () => ({
        edCareCoordinationActivitiesService: {
            getAll: vi.fn(),
            show: vi.fn(),
        },
    }),
)

function mockResponse({
    data = [],
    total = 0,
    current_page = 1,
    per_page = 10,
} = {}) {
    return { data, meta: { total, current_page, per_page } }
}

describe('useTriageCareCoordination', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        edCareCoordinationActivitiesService.getAll.mockResolvedValue(
            mockResponse(),
        )
    })

    it('only ever asks for activities that are not completed', async () => {
        await useTriageCareCoordination().fetchAll()

        expect(edCareCoordinationActivitiesService.getAll).toHaveBeenCalledWith({
            page: 1,
            per_page: 10,
            sort_by: 'created_at',
            sort_dir: 'desc',
            status: 'false',
        })
    })

    it('maps the filter bar values onto the endpoint params', async () => {
        const store = useTriageCareCoordination()

        store.setFilters({
            me_mode: true,
            state: ['TX'],
            branch: [3],
            region: [4],
            assignee: [5],
            status: 'false',
            search: 'Doe',
        })
        await vi.waitFor(() =>
            expect(edCareCoordinationActivitiesService.getAll).toHaveBeenCalled(),
        )

        expect(edCareCoordinationActivitiesService.getAll).toHaveBeenCalledWith({
            page: 1,
            per_page: 10,
            sort_by: 'created_at',
            sort_dir: 'desc',
            me_mode: true,
            state: ['TX'],
            branch_ids: [3],
            region_ids: [4],
            assignee_ids: [5],
            search: 'Doe',
            status: 'false',
        })
    })

    it('keeps the not-completed scope after Clear All', async () => {
        const store = useTriageCareCoordination()

        store.setFilters({ state: ['TX'] })
        store.resetFilters()
        await vi.waitFor(() =>
            expect(
                edCareCoordinationActivitiesService.getAll,
            ).toHaveBeenCalledTimes(2),
        )

        expect(
            edCareCoordinationActivitiesService.getAll,
        ).toHaveBeenLastCalledWith(expect.objectContaining({ status: 'false' }))
        expect(
            edCareCoordinationActivitiesService.getAll.mock.lastCall[0],
        ).not.toHaveProperty('state')
    })

    it("opens Create Note for the activity's hospitalization tracker", async () => {
        edCareCoordinationActivitiesService.show.mockResolvedValue({
            id: 7,
            hospitalization_tracker_id: 42,
            tracker_status: 'In Progress',
        })
        const trackerStore = useHospitalizationTracker()

        const opened = await useTriageCareCoordination().openCreateNote({
            id: 7,
        })

        expect(edCareCoordinationActivitiesService.show).toHaveBeenCalledWith(7)
        expect(opened).toBe(true)
        expect(trackerStore.noteModalOpen).toBe(true)
        expect(trackerStore.noteModalTrackerId).toBe(42)
    })

    it('does not open Create Note when the tracker no longer accepts notes', async () => {
        edCareCoordinationActivitiesService.show.mockResolvedValue({
            id: 7,
            hospitalization_tracker_id: 42,
            tracker_status: 'Closed',
        })
        const trackerStore = useHospitalizationTracker()

        const opened = await useTriageCareCoordination().openCreateNote({
            id: 7,
        })

        expect(opened).toBe(false)
        expect(trackerStore.noteModalOpen).toBe(false)
    })

    it('does not open Create Note when the activity has no tracker', async () => {
        edCareCoordinationActivitiesService.show.mockResolvedValue({
            id: 7,
            hospitalization_tracker_id: null,
        })

        expect(
            await useTriageCareCoordination().openCreateNote({ id: 7 }),
        ).toBe(false)
        expect(useHospitalizationTracker().noteModalOpen).toBe(false)
    })
})
