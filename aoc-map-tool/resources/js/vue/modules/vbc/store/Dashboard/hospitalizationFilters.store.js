import { defineStore } from 'pinia'
import { ref } from 'vue'
import { FiltersService, toOptionList } from '@/modules/vbc/services/filters.service.js'

// FilterBucketEnum::HOSPITALIZATION on the backend — its Status options are limited to the statuses the bucket shows.
const HOSPITALIZATION_BUCKET = 'HOSPITALIZATION'

export const useHospitalizationFiltersStore = defineStore('hospitalizationFilters', () => {
    const options = ref({
        states:    [],
        branches:  [],
        regions:   [],
        assignees: [],
        statuses:  [],
    })

    const values = ref({
        me_mode:   false,
        state:     [],
        branch:    [],
        region:    [],
        assignee:  [],
        status:    [],
        date_from: '',
        date_to:   '',
        search:    '',
    })

    async function fetchOptions() {
        const data    = await FiltersService.getOptions(HOSPITALIZATION_BUCKET)
        options.value = {
            states:    toOptionList(data.states),
            branches:  toOptionList(data.branches),
            regions:   toOptionList(data.regions),
            assignees: toOptionList(data.assignees),
            statuses:  toOptionList(data.hospitalizationStatuses),
        }
    }

    function reset() {
        values.value = {
            me_mode:   false,
            state:     [],
            branch:    [],
            region:    [],
            assignee:  [],
            status:    [],
            date_from: '',
            date_to:   '',
            search:    '',
        }
    }

    return { options, values, fetchOptions, reset }
})
