import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import HospitalizedClientSearch from '@/modules/vbc/components/CareCoordinationActivity/HospitalizedClientSearch.vue'
import { careCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js'

vi.mock('@/modules/vbc/services/Dashboard/careCoordinationActivities.service.js', () => ({
    careCoordinationActivitiesService: {
        searchEligibleClients: vi.fn(),
    },
}))

const CLIENT = {
    patient_id: 12345,
    patient_account: '12345-PDN',
    full_name: 'John Taylor',
    hospitalization_tracker_id: 6789,
}

describe('HospitalizedClientSearch', () => {
    beforeEach(() => {
        vi.clearAllMocks()
    })

    it('debounces input and searches via the service', async () => {
        careCoordinationActivitiesService.searchEligibleClients.mockResolvedValue([CLIENT])
        const wrapper = mount(HospitalizedClientSearch)

        await wrapper.find('input').setValue('John')
        await new Promise((r) => window.setTimeout(r, 350))
        await wrapper.vm.$nextTick()

        expect(
            careCoordinationActivitiesService.searchEligibleClients,
        ).toHaveBeenCalledWith('John')
        expect(wrapper.text()).toContain(
            'John Taylor, ID 12345-PDN - Hospitalization Tracker ID # 6789',
        )
    })

    it('emits the selected client and clears the results', async () => {
        careCoordinationActivitiesService.searchEligibleClients.mockResolvedValue([CLIENT])
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
