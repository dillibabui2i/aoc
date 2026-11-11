import { defineStore } from 'pinia'
import { ref } from 'vue'
import { FiltersService, toOptionList } from '@/modules/vbc/services/filters.service.js'

export const useAllHospitalizationFiltersStore = defineStore('AllHospitalizationFilters', () => {

    const options = ref({
        exportTypes:    [],
        states:         [],
        branches:       [],
        regions:        [],
        assignees:      [],
        statuses:       [],
        levels:         [],
        facilityNames:  [],
    })

    const DEFAULT_VALUES = {
        export_type:          '',
        state:                '',
        branch_ids:           '',
        region_ids:           '',
        assignee_ids:         '',
        statuses:             '',
        levels:               '',
        facility_names:       '',
        admission_date_from:  '',
        admission_date_to:    '',
        search:               '',
    }

    const values = ref({ ...DEFAULT_VALUES })

    const loading = ref(false)
    const error   = ref(null)

    async function fetchOptions() {
        loading.value = true
        error.value   = null
        try {
            const data      = await FiltersService.getOptions()
            options.value   = {
                exportTypes:   toOptionList(data.export_types),
                states:        toOptionList(data.states),
                branches:      toOptionList(data.branches),
                regions:       toOptionList(data.regions),
                assignees:     toOptionList(data.assignees),
                statuses:      toOptionList(data.hospitalizationStatuses),
                levels:        toOptionList(data.level),
                facilityNames: toOptionList(data.facility_names),
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
