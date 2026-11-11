import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'
import { buildBucketQueryParams } from '@/modules/vbc/services/filters.service.js'

export const useEdCareCoordinationActivities = defineStore(
    'edCareCoordinationActivities',
    () => {
        const list = ref([])
        const loading = ref(false)
        const error = ref(null)

        // pagination
        const total = ref(0)
        const page = ref(1)
        const perPage = ref(10)

        // sorting — default: Patient Name, A to Z
        const sortBy = ref('patient_name')
        const sortDir = ref('asc')

        const totalPages = computed(
            () => Math.ceil(total.value / perPage.value) || 1,
        )

        const activeFilters = ref({})

        // Care Coordination modal lifecycle (AC3-AC13). `editingActivity` holds the full
        // CareCoordinationResource payload (fetched by `openActivity`) while editing/viewing an existing
        // activity; `null` while creating. `confirmedClient` holds the patient search result once stage 1
        // is submitted in create mode — its `tracker_status`/`hospitalization_tracker_id` feed the modal's
        // header the same way `editingActivity`'s do.
        const modalOpen = ref(false)
        const editingActivity = ref(null)
        const confirmedClient = ref(null)
        // Optional hook for callers outside the dashboard bucket (the Hospitalization Tracker modal's
        // Care Coordination tab) that keep their own separate, tracker-scoped list — `fetchAll()` below
        // only ever refreshes the dashboard bucket's own paginated view, which such a caller may not even
        // have mounted.
        const onSavedCallback = ref(null)

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

            const query = buildBucketQueryParams({
                filters: activeFilters.value,
                page: page.value,
                perPage: perPage.value,
                sortBy: sortBy.value,
                sortDir: sortDir.value,
                extra: {
                    status: activeFilters.value.status,
                },
            })

            try {
                const res = await edCareCoordinationActivitiesService.getAll(query)
                list.value    = res.data
                total.value   = res.meta.total
                page.value    = res.meta.current_page
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

        function openCreate(onSaved = null) {
            onSavedCallback.value = onSaved
            editingActivity.value = null
            confirmedClient.value = null
            modalOpen.value = true
        }

        async function openActivity(row, onSaved = null) {
            onSavedCallback.value = onSaved
            editingActivity.value = await edCareCoordinationActivitiesService.show(row.id)
            confirmedClient.value = null
            modalOpen.value = true
        }

        // The patient search (AC5) already resolves the client's own active tracker, so its result
        // doubles as everything the modal's header needs — no separate tracker fetch.
        function confirmClientForCreate(client) {
            confirmedClient.value = client
        }

        function closeModal() {
            modalOpen.value = false
            editingActivity.value = null
            confirmedClient.value = null
            onSavedCallback.value = null
        }

        async function createActivity(fields) {
            const client = confirmedClient.value
            if (!client) return

            await edCareCoordinationActivitiesService.create({
                hospitalization_tracker_id: client.hospitalization_tracker_id,
                ...fields,
            })

            // PD-3969: no dashboard-bucket refetch here — the callers (Triage bucket, ED tracker modal's tab)
            // keep their own lists and refresh them through `onSaved`.
            await onSavedCallback.value?.()
            closeModal()
        }

        async function updateActivity(fields) {
            const activity = editingActivity.value
            if (!activity) return

            await edCareCoordinationActivitiesService.update(activity.id, fields)

            // PD-3969: no dashboard-bucket refetch here — the callers (Triage bucket, ED tracker modal's tab)
            // keep their own lists and refresh them through `onSaved`.
            await onSavedCallback.value?.()
            closeModal()
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
            modalOpen,
            editingActivity,
            confirmedClient,
            fetchAll,
            setPage,
            setPerPage,
            setSort,
            setFilters,
            resetFilters,
            openCreate,
            openActivity,
            confirmClientForCreate,
            closeModal,
            createActivity,
            updateActivity,
        }
    },
)
