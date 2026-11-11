import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ImmunizationBucketService } from '@/modules/vbc/services/Dashboard/immunizationBucket.service.js'

function cleanQuery(query) {
    return Object.fromEntries(
        Object.entries(query).filter(
            ([_, v]) => v !== '' && v !== null && v !== undefined && !(Array.isArray(v) && v.length === 0)
        )
    )
}

export const useImmunizationBucket = defineStore('immunizationBucket', () => {
    const list = ref([])
    const loading = ref(false)
    const error = ref(null)

    const total = ref(0)
    const page = ref(1)
    const perPage = ref(10)

    // Default order of the bucket: Patient Name A-Z (the backend adds due date closest to today as 2nd key).
    const sortBy = ref('patient_name')
    const sortDir = ref('asc')

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

        const query = cleanQuery({
            page: page.value,
            per_page: perPage.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
            ...activeFilters.value,
        })

        try {
            const res = await ImmunizationBucketService.getAll(query)
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
