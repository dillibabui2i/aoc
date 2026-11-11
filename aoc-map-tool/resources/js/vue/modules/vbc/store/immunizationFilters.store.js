import { defineStore } from 'pinia'
import { ref } from 'vue'
import { FiltersService, toOptionList } from '@/modules/vbc/services/filters.service.js'

// FilterBucketEnum::IMMUNIZATION on the backend — asks for the immunization-specific options only.
const IMMUNIZATION_BUCKET = 'IMMUNIZATION'

export const useAllImmunizationFiltersStore = defineStore('AllImmunizationFilters', () => {

    const options = ref({
        vaccines:  [],
        states:    [],
        branches:  [],
        statuses:  [],
        payers:    [],
    })

    const DEFAULT_VALUES = {
        vaccine_cvx_codes:   '',
        state:               '',
        branch_ids:          '',
        statuses:            '',
        payers:              '',
        earliest_date_from:  '',
        earliest_date_to:    '',
        search:              '',
    }

    const values = ref({ ...DEFAULT_VALUES })

    const loading = ref(false)
    const error   = ref(null)

    async function fetchOptions() {
        loading.value = true
        error.value   = null
        try {
            const data    = await FiltersService.getOptions(IMMUNIZATION_BUCKET)
            options.value = {
                vaccines: toOptionList(data.vaccines),
                states:   toOptionList(data.states),
                branches: toOptionList(data.branches),
                statuses: toOptionList(data.vaccineDoseStatuses),
                payers:   toOptionList(data.payers),
            }
        } catch (e) {
            error.value = e?.response?.data?.message ?? 'Failed to load filters'
        } finally {
            loading.value = false
        }
    }

    function setValue(key, val) {
        values.value[key] = val
    }

    function reset() {
        values.value = { ...DEFAULT_VALUES }
    }

    return { options, values, loading, error, fetchOptions, setValue, reset }
})
