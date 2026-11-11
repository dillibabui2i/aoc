import { defineStore } from 'pinia'
import { ref } from 'vue'
import { FiltersService, toOptionList } from '@/modules/vbc/services/filters.service.js'

// HospitalizationTrackerFollowupStatusEnum has 10 cases, but the Communications bucket's "Select Status"
// dropdown shows only these 5 — the ones a Hospitalization (IP) follow-up actually goes through. The
// others (NOT_STARTED/FIRST_ATTEMPT/SECOND_ATTEMPT/THIRD_ATTEMPT/NEW) stay valid followup statuses
// elsewhere, just not offered here. ED Visits follow-ups will likely go through a different status set —
// don't reuse this list for that bucket once it exists, give it its own.
const HOSPITALIZATION_FOLLOWUP_STATUS_OPTIONS = ['OPEN', 'SCHEDULED', 'COMPLETED', 'ATTEMPTED', 'CANCELED']

export const useCommunicationsFiltersStore = defineStore('communicationsFilters', () => {
    const options = ref({
        states:    [],
        branches:  [],
        regions:   [],
        assignees: [],
        statuses:  [],
    })

    const values = ref({
        me_mode:  false,
        state:    [],
        branch:   [],
        region:   [],
        assignee: [],
        status:   [],
        search:   '',
    })

    async function fetchOptions() {
        const data    = await FiltersService.getOptions()
        options.value = {
            states:    toOptionList(data.states),
            branches:  toOptionList(data.branches),
            regions:   toOptionList(data.regions),
            assignees: toOptionList(data.assignees),
            statuses:  toOptionList(data.followupStatuses).filter((opt) => HOSPITALIZATION_FOLLOWUP_STATUS_OPTIONS.includes(opt.value)),
        }
    }

    function reset() {
        values.value = {
            me_mode:  false,
            state:    [],
            branch:   [],
            region:   [],
            assignee: [],
            status:   [],
            search:   '',
        }
    }

    return { options, values, fetchOptions, reset }
})
