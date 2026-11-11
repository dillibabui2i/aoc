import { defineStore } from 'pinia'
import { ref } from 'vue'
import { FiltersService, toOptionList } from '@/modules/vbc/services/filters.service.js'

// Care Coordination has no status enum on the backend — it's backed by a single `is_completed`
// boolean (see CareCoordinationFilterDTO), so the "Select Status" dropdown is a static 2-option list.
const STATUS_OPTIONS = [
    { value: 'false', text: 'Open' },
    { value: 'true', text: 'Completed' },
]

export const useCareCoordinationFiltersStore = defineStore('careCoordinationFilters', () => {
    const options = ref({
        states:    [],
        branches:  [],
        regions:   [],
        assignees: [],
        statuses:  STATUS_OPTIONS,
    })

    const values = ref({
        me_mode:  false,
        state:    [],
        branch:   [],
        region:   [],
        assignee: [],
        status:   '',
        search:   '',
    })

    async function fetchOptions() {
        const data    = await FiltersService.getOptions()
        options.value = {
            states:    toOptionList(data.states),
            branches:  toOptionList(data.branches),
            regions:   toOptionList(data.regions),
            assignees: toOptionList(data.assignees),
            statuses:  STATUS_OPTIONS,
        }
    }

    function reset() {
        values.value = {
            me_mode:  false,
            state:    [],
            branch:   [],
            region:   [],
            assignee: [],
            status:   '',
            search:   '',
        }
    }

    return { options, values, fetchOptions, reset }
})
