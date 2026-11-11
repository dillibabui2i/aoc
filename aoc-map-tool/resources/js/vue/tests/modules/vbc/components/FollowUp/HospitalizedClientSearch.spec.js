import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import HospitalizedClientSearch from '@/modules/vbc/components/FollowUp/HospitalizedClientSearch.vue'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: {
        searchHospitalizedClientsForFollowUp: vi.fn(),
    },
}))

const CLIENT = {
    clientId: '12345-PDN',
    fullName: 'John Taylor',
    trackerId: '#12346',
}

describe('HospitalizedClientSearch (FollowUp)', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('debounces input and searches via the service', async () => {
        HospitalizationTrackerService.searchHospitalizedClientsForFollowUp.mockResolvedValue(
            [CLIENT],
        )
        const wrapper = mount(HospitalizedClientSearch)

        await wrapper.find('input').setValue('John')
        await new Promise((r) => window.setTimeout(r, 350))
        await wrapper.vm.$nextTick()

        expect(
            HospitalizationTrackerService.searchHospitalizedClientsForFollowUp,
        ).toHaveBeenCalledWith('John')
        expect(wrapper.text()).toContain('John Taylor, ID 12345-PDN')
    })

    it('emits the selected client and clears the results', async () => {
        HospitalizationTrackerService.searchHospitalizedClientsForFollowUp.mockResolvedValue(
            [CLIENT],
        )
        const wrapper = mount(HospitalizedClientSearch)
        await wrapper.find('input').setValue('John')
        await new Promise((r) => window.setTimeout(r, 350))
        await wrapper.vm.$nextTick()

        await wrapper
            .find('.hospitalized-client-search__result')
            .trigger('click')

        expect(wrapper.emitted('select')).toEqual([[CLIENT]])
        expect(
            wrapper.find('.hospitalized-client-search__results').exists(),
        ).toBe(false)
    })
})
