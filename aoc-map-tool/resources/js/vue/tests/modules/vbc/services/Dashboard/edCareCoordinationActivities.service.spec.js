import { describe, it, expect, vi, beforeEach } from 'vitest'
import client from '@/core/api/client.js'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'
import { careCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js'

vi.mock('@/core/api/client.js', () => ({
    default: { get: vi.fn(), post: vi.fn(), patch: vi.fn() },
}))
vi.mock('ziggy-js', () => ({ route: (name) => `/${name}` }))

describe('Care Coordination services state their encounter type (PD-3969)', () => {
    beforeEach(() => {
        vi.clearAllMocks()
        client.get.mockResolvedValue({ data: { data: [] } })
        client.post.mockResolvedValue({ data: { data: {} } })
        client.patch.mockResolvedValue({ data: { data: {} } })
    })

    it('ED service: list and search send encounter_type=ED', async () => {
        await edCareCoordinationActivitiesService.getAll({ page: 1 })
        await edCareCoordinationActivitiesService.searchEligibleClients('Jo')

        expect(client.get).toHaveBeenNthCalledWith(1, '/care-coordination.index', {
            params: { page: 1, encounter_type: 'ED' },
        })
        expect(client.get).toHaveBeenNthCalledWith(2, '/care-coordination.clients.index', {
            params: { search: 'Jo', encounter_type: 'ED' },
        })
    })

    it('ED service: create and update send encounter_type=ED', async () => {
        await edCareCoordinationActivitiesService.create({ hospitalization_tracker_id: 5 })
        await edCareCoordinationActivitiesService.update(7, { notes: 'x' })

        expect(client.post).toHaveBeenCalledWith('/care-coordination.store', {
            hospitalization_tracker_id: 5,
            encounter_type: 'ED',
        })
        expect(client.patch).toHaveBeenCalledWith('/care-coordination.update', {
            notes: 'x',
            encounter_type: 'ED',
        })
    })

    it('IP service: every call sends encounter_type=IP', async () => {
        await careCoordinationActivitiesService.getAll({ page: 1 })
        await careCoordinationActivitiesService.searchEligibleClients('Jo')
        await careCoordinationActivitiesService.create({ a: 1 })
        await careCoordinationActivitiesService.update(7, { b: 2 })

        expect(client.get.mock.calls[0][1].params.encounter_type).toBe('IP')
        expect(client.get.mock.calls[1][1].params.encounter_type).toBe('IP')
        expect(client.post.mock.calls[0][1].encounter_type).toBe('IP')
        expect(client.patch.mock.calls[0][1].encounter_type).toBe('IP')
    })
})
