import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { EdVisitsService } from '@/modules/vbc/services/Dashboard/edVisits.service.js'
import { buildBucketQueryParams } from '@/modules/vbc/services/filters.service.js'

// EncounterTypeEnum::ED on the backend. Always sent: this bucket is the Hospitalization data narrowed to
// ED encounters, and that narrowing is the caller's job (the endpoint itself only scopes the status).
export const ED_ENCOUNTER_TYPE = 'ED'

export const useEdVisits = defineStore('edVisits', () => {
    const list = ref([])
    const loading = ref(false)
    const error = ref(null)

    // pagination
    const total = ref(0)
    const page = ref(1)
    const perPage = ref(10)

    // sorting — default: Admission Date, newest first
    const sortBy = ref('admission_date')
    const sortDir = ref('desc')

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
                encounter_types: [ED_ENCOUNTER_TYPE],
                statuses: activeFilters.value.status,
                admission_date_from: activeFilters.value.date_from,
                admission_date_to: activeFilters.value.date_to,
            },
        })

        try {
            const res = await EdVisitsService.getAll(query)
            list.value = res.data
            total.value = res.meta.total
            page.value = res.meta.current_page
            perPage.value = res.meta.per_page
        } catch (e) {
            error.value = e?.response?.data?.message ?? 'Failed to load data'
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
