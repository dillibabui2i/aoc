import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { AllHospitalizationService } from '@/modules/vbc/services/allHospitalization.service.js'
import { HOSPITALIZATION_ENCOUNTER_TYPE } from '@/modules/vbc/services/filters.service.js'

function cleanQuery(query) {
    return Object.fromEntries(
        Object.entries(query).filter(([_, v]) => v !== '' && v !== null && v !== undefined && !(Array.isArray(v) && v.length === 0))
    )
}

function filenameFromResponse(response, fallback) {
    const disposition = response.headers?.['content-disposition']
    const match = disposition && disposition.match(/filename="?([^"]+)"?/)
    return match ? match[1] : fallback
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = filename
    document.body.appendChild(link)
    link.click()
    link.remove()
    URL.revokeObjectURL(url)
}

export const useAllHospitalization = defineStore('allHospitalization', () => {
    const list    = ref([])
    const loading = ref(false)
    const error   = ref(null)

    // pagination
    const total   = ref(0)
    const page    = ref(1)
    const perPage = ref(10)

    // sorting
    const sortBy  = ref(null)
    const sortDir = ref('asc')

    const totalPages = computed(() => Math.ceil(total.value / perPage.value) || 1)

    const activeFilters = ref({})

    // export
    const exporting   = ref(false)
    const exportError = ref(null)

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
        error.value   = null

        const query = cleanQuery({
            page:     page.value,
            per_page: perPage.value,
            sort_by:  sortBy.value,
            sort_dir: sortDir.value,
            encounter_types: [HOSPITALIZATION_ENCOUNTER_TYPE],
            ...activeFilters.value,
        })

        try {
            const res = await AllHospitalizationService.getAll(query)
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

    async function exportAll() {
        exporting.value   = true
        exportError.value = null

        const query = cleanQuery({
            sort_by:  sortBy.value,
            sort_dir: sortDir.value,
            encounter_types: [HOSPITALIZATION_ENCOUNTER_TYPE],
            ...activeFilters.value,
        })

        try {
            const res = await AllHospitalizationService.export(query)
            downloadBlob(res.data, filenameFromResponse(res, 'all_hospitalizations.csv'))
        } catch (e) {
            exportError.value = e?.response?.data?.message ?? 'Failed to export data'
        } finally {
            exporting.value = false
        }
    }

    function setPage(val) {
        page.value = val
        fetchAll()
    }

    function setPerPage(val) {
        perPage.value = val
        page.value    = 1
        fetchAll()
    }

    function setSort(key, dir) {
        sortBy.value  = key
        sortDir.value = dir
        page.value    = 1
        fetchAll()
    }

    return {
        list, loading, error,
        total, page, perPage, totalPages,
        sortBy, sortDir,
        exporting, exportError,
        fetchAll, setPage, setPerPage, setSort, setFilters, resetFilters, exportAll
    }
})
