import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { CommunicationService } from '@/modules/vbc/services/Dashboard/communication.service.js'
import { buildBucketQueryParams, HOSPITALIZATION_ENCOUNTER_TYPE } from '@/modules/vbc/services/filters.service.js'

export const useCommunication = defineStore('communication', () => {
    const list = ref([])
    const loading = ref(false)
    const error = ref(null)

    // pagination
    const total = ref(0)
    const page = ref(1)
    const perPage = ref(10)

    // sorting — default: Due Date, oldest first
    const sortBy = ref('followup_date')
    const sortDir = ref('asc')

    const totalPages = computed(
        () => Math.ceil(total.value / perPage.value) || 1,
    )

    const activeFilters = ref({})

    function setFilters(filters) {
        activeFilters.value = filters
        page.value = 1
        fetchAll()
    }

    function resetFilters() {
        activeFilters.value = {}
        page.value = 1
        fetchAll()
    }

    // Communications bucket = the "Hospitalization Follow-ups" bucket — served by `follow-up.index`
    // (app/VBC/Http/Controllers/Followup/FollowupController::getFollowups).
    async function fetchAll() {
        loading.value = true
        error.value = null

        const query = buildBucketQueryParams({
            filters: activeFilters.value,
            page: page.value,
            perPage: perPage.value,
            sortBy: sortBy.value,
            sortDir: sortDir.value,
            extra: {
                encounter_types: [HOSPITALIZATION_ENCOUNTER_TYPE],
                statuses: activeFilters.value.status,
            },
        })

        try {
            const res = await CommunicationService.getAll(query)
            list.value    = res.data
            total.value   = res.meta.total
            page.value    = res.meta.current_page
            perPage.value = res.meta.per_page
        } catch (e) {
            error.value = e?.message ?? 'Failed to load data'
        } finally {
            loading.value = false
        }
    }

    function setPage(val) {
        page.value = val
        fetchAll()
    }

    function setPerPage(val) {
        perPage.value = val
        page.value = 1
        fetchAll()
    }

    function setSort(key, dir) {
        sortBy.value = key
        sortDir.value = dir
        page.value = 1
        fetchAll()
    }

    return {
        list,
        loading,
        error,
        total,
        page,
        perPage,
        totalPages,
        sortBy,
        sortDir,
        fetchAll,
        setPage,
        setPerPage,
        setSort,
        setFilters,
        resetFilters,
    }
})
