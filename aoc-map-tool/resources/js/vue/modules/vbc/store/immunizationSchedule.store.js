import { defineStore } from 'pinia'
import { ref } from 'vue'
import { ImmunizationScheduleService } from '@/modules/vbc/services/immunizationSchedule.service.js'

export const PER_PAGE_OPTIONS = [10, 25, 50, 100]

const defaultFilters = () => ({
    vaccine_group_code: '',
    vaccine_cvx_code: '',
    states: [],
    status: '',
    search: '',
})

function cleanQuery(query) {
    return Object.fromEntries(
        Object.entries(query).filter(([_, v]) => v !== '' && v !== null && v !== undefined && !(Array.isArray(v) && v.length === 0))
    )
}

export const useImmunizationSchedule = defineStore('immunizationSchedule', () => {
    const list = ref([])
    const loading = ref(false)
    const error = ref(null)

    // pagination
    const total = ref(0)
    const page = ref(1)
    const perPage = ref(10)

    // sorting — default order is Vaccine Category (A-Z), then Vaccine Name (A-Z) as the backend's secondary key
    const sortBy = ref('vaccine_category')
    const sortDir = ref('asc')

    // `filters` is what the filter bar edits; `activeFilters` is what the last Search/Clear applied to the list.
    const filters = ref(defaultFilters())
    const activeFilters = ref({})

    // Add / Edit modal. `editing` is the saved schedule being edited (null = Add); `serverErrors` holds the
    // backend's 422 `errors` for the modal to show next to the fields it can't check itself (duplicates, overlaps).
    const modalOpen = ref(false)
    const editing = ref(null)
    const options = ref(null)
    const saving = ref(false)
    const serverErrors = ref({})

    async function fetchAll() {
        loading.value = true
        error.value = null

        try {
            const res = await ImmunizationScheduleService.getAll(
                cleanQuery({
                    page: page.value,
                    per_page: perPage.value,
                    sort_by: sortBy.value,
                    sort_dir: sortDir.value,
                    ...activeFilters.value,
                })
            )
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

    function applyFilters() {
        activeFilters.value = { ...filters.value }
        page.value = 1
        fetchAll()
    }

    function resetFilters() {
        filters.value = defaultFilters()
        applyFilters()
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

    // UiTable emits `update:sortBy` and then `update:sortDir` when a new column is clicked, and only
    // `update:sortDir` when the same column is clicked again — so only the direction triggers the fetch.
    function setSortBy(key) {
        sortBy.value = key
    }

    function setSortDir(dir) {
        sortDir.value = dir
        page.value = 1
        fetchAll()
    }

    async function loadOptions() {
        if (options.value) return
        options.value = await ImmunizationScheduleService.getOptions()
    }

    function openAdd() {
        editing.value = null
        serverErrors.value = {}
        modalOpen.value = true
    }

    async function openEdit(scheduleId) {
        try {
            editing.value = await ImmunizationScheduleService.getOne(scheduleId)
        } catch (e) {
            error.value = e?.response?.data?.message ?? 'Failed to load the schedule'
            return
        }
        serverErrors.value = {}
        modalOpen.value = true
    }

    function closeModal() {
        modalOpen.value = false
        editing.value = null
        serverErrors.value = {}
    }

    // Resolves true when saved (modal closed, table refreshed); false on a validation failure, which keeps
    // the modal open with everything the user entered — any other error is not ours to swallow.
    async function saveSchedule(payload) {
        saving.value = true
        serverErrors.value = {}

        try {
            if (editing.value) {
                await ImmunizationScheduleService.update(editing.value.id, payload)
            } else {
                await ImmunizationScheduleService.create(payload)
            }
        } catch (e) {
            if (e?.response?.status === 422) {
                serverErrors.value = e.response.data.errors ?? {}
                return false
            }
            throw e
        } finally {
            saving.value = false
        }

        closeModal()
        await fetchAll()
        return true
    }

    return {
        list, loading, error,
        modalOpen, editing, options, saving, serverErrors,
        loadOptions, openAdd, openEdit, closeModal, saveSchedule,
        total, page, perPage,
        sortBy, sortDir,
        filters, applyFilters, resetFilters,
        fetchAll, setPage, setPerPage, setSortBy, setSortDir,
    }
})
