import { describe, it, expect, vi, beforeEach } from 'vitest'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'
import apiClient from '@/core/api/client.js'

vi.mock('@/core/api/client.js', () => ({
    default: { post: vi.fn(), patch: vi.fn() },
}))

vi.mock('ziggy-js', () => ({ route: (name) => name }))

describe('EdVisitTrackerService diagnosis (PD-3970)', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('sends encounter_type ED on all three writes', async () => {
        apiClient.post.mockResolvedValue({ data: { data: { id: 1 } } })
        apiClient.patch.mockResolvedValue({ data: { data: { id: 1 } } })

        await EdVisitTrackerService.createDiagnosis(1, { rank: 1 })
        await EdVisitTrackerService.updateDiagnosis(1, 2, { rank: 2 })
        await EdVisitTrackerService.updateWasDiagnosisMade(1, true)

        expect(apiClient.post.mock.calls[0][1]).toEqual({
            rank: 1,
            encounter_type: 'ED',
        })
        expect(apiClient.patch.mock.calls[0][1]).toEqual({
            rank: 2,
            encounter_type: 'ED',
        })
        expect(apiClient.patch.mock.calls[1][1]).toEqual({
            was_diagnosis_made: true,
            encounter_type: 'ED',
        })
    })
})
