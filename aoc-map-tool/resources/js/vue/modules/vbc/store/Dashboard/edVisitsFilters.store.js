import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
    FiltersService,
    toOptionList,
} from '@/modules/vbc/services/filters.service.js'

const ED_VISITS_BUCKET = 'ED_VISITS'

function defaultValues() {
    return {
        me_mode: false,
        state: [],
        branch: [],
        region: [],
        assignee: [],
        status: [],
        date_from: '',
        date_to: '',
        search: '',
    }
}

export const useEdVisitsFiltersStore = defineStore('edVisitsFilters', () => {
    const options = ref({
        states: [],
        branches: [],
        regions: [],
        assignees: [],
        statuses: [],
    })

    const values = ref(defaultValues())

    async function fetchOptions() {
        const data = await FiltersService.getOptions(ED_VISITS_BUCKET)
        options.value = {
            states: toOptionList(data.states),
            branches: toOptionList(data.branches),
            regions: toOptionList(data.regions),
            assignees: toOptionList(data.assignees),
            statuses: toOptionList(data.hospitalizationStatuses),
        }
    }

    function reset() {
        values.value = defaultValues()
    }

    return { options, values, fetchOptions, reset }
})
