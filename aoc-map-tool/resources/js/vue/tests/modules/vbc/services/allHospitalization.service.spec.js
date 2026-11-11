import { describe, it, expect, vi } from 'vitest'
import client from '@/core/api/client.js'
import { AllHospitalizationService } from '@/modules/vbc/services/allHospitalization.service.js'

vi.mock('@/core/api/client.js', () => ({
    default: {
        get: vi.fn(),
    },
}))

describe('AllHospitalizationService', () => {
    it('requests the hospitalization list with the given params and unwraps the response', async () => {
        const data = { data: [{ id: 1 }], meta: { total: 1 } }
        client.get.mockResolvedValue({ data })

        const params = { page: 1, per_page: 10 }
        const result = await AllHospitalizationService.getAll(params)

        expect(client.get).toHaveBeenCalledWith('/hospitalization.all-data', {
            params,
        })
        expect(result).toEqual(data)
    })
})
