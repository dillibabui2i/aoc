import { describe, it, expect, vi } from 'vitest'
import client from '@/core/api/client.js'
import { FiltersService } from '@/modules/vbc/services/filters.service.js'

vi.mock('@/core/api/client.js', () => ({
    default: {
        get: vi.fn(),
    },
}))

describe('FiltersService', () => {
    it('requests filter options and unwraps the response', async () => {
        const data = { states: ['CA'] }
        client.get.mockResolvedValue({ data })

        const result = await FiltersService.getOptions()

        expect(client.get).toHaveBeenCalledWith('/filters.index')
        expect(result).toEqual(data)
    })
})
