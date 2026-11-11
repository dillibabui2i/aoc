import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdCareCoordinationActivities } from '@/modules/vbc/store/Dashboard/edCareCoordinationActivities.store.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'

vi.mock('@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js', () => ({
    edCareCoordinationActivitiesService: {
        getAll: vi.fn(),
        show: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
    },
}))

describe('useEdCareCoordinationActivities', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    it('create: posts the confirmed ED tracker, refreshes the caller via onSaved, then closes', async () => {
        const store = useEdCareCoordinationActivities()
        const onSaved = vi.fn()
        edCareCoordinationActivitiesService.create.mockResolvedValue({})

        store.openCreate(onSaved)
        store.confirmClientForCreate({ hospitalization_tracker_id: 31 })
        await store.createActivity({ care_coordination_type_id: 2, completed_at: '' })

        expect(edCareCoordinationActivitiesService.create).toHaveBeenCalledWith({
            hospitalization_tracker_id: 31,
            care_coordination_type_id: 2,
            completed_at: '',
        })
        expect(onSaved).toHaveBeenCalledTimes(1)
        expect(store.modalOpen).toBe(false)
    })

    it('update: saves, refreshes the caller via onSaved, then closes', async () => {
        const store = useEdCareCoordinationActivities()
        const onSaved = vi.fn()
        edCareCoordinationActivitiesService.show.mockResolvedValue({ id: 9 })
        edCareCoordinationActivitiesService.update.mockResolvedValue({})

        await store.openActivity({ id: 9 }, onSaved)
        await store.updateActivity({ notes: 'x' })

        expect(edCareCoordinationActivitiesService.update).toHaveBeenCalledWith(9, { notes: 'x' })
        expect(onSaved).toHaveBeenCalledTimes(1)
        expect(store.modalOpen).toBe(false)
    })
})
