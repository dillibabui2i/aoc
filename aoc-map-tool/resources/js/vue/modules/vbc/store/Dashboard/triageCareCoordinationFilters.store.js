import { defineStore } from 'pinia'
import { ref } from 'vue'
import {
    FiltersService,
    toOptionList,
} from '@/modules/vbc/services/filters.service.js'

// Care Coordination has no status enum on the backend — only the `is_completed` flag behind the `status`
// param (see CareCoordinationFilterDTO). This bucket only ever shows activities that aren't completed
// (AC13), so "Open" is the only status there is to pick.
export const OPEN_STATUS = 'false'
const STATUS_OPTIONS = [{ value: OPEN_STATUS, text: 'Open' }]

function defaultValues() {
    return {
        me_mode: false,
        state: [],
        branch: [],
        region: [],
        assignee: [],
        status: '',
        search: '',
    }
}

export const useTriageCareCoordinationFiltersStore = defineStore(
    'triageCareCoordinationFilters',
    () => {
        const options = ref({
            states: [],
            branches: [],
            regions: [],
            assignees: [],
            statuses: STATUS_OPTIONS,
        })

        const values = ref(defaultValues())

        async function fetchOptions() {
            const data = await FiltersService.getOptions()
            options.value = {
                states: toOptionList(data.states),
                branches: toOptionList(data.branches),
                regions: toOptionList(data.regions),
                assignees: toOptionList(data.assignees),
                statuses: STATUS_OPTIONS,
            }
        }

        function reset() {
            values.value = defaultValues()
        }

        return { options, values, fetchOptions, reset }
    },
)
