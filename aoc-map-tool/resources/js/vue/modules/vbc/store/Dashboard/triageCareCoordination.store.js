import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { edCareCoordinationActivitiesService } from '@/modules/vbc/services/Dashboard/edCareCoordinationActivities.service.js'
import { isNotesEligible } from '@/modules/vbc/services/hospitalizationTracker.service.js'
import { buildBucketQueryParams } from '@/modules/vbc/services/filters.service.js'
import { OPEN_STATUS } from '@/modules/vbc/store/Dashboard/triageCareCoordinationFilters.store.js'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// List state for the Triage dashboard's "Care Coordination Activities" bucket (PD-4057). Kept apart from
// `useCareCoordinationActivities` — the ICM dashboard's bucket, which can be on the same page with its
// own filters/page. PD-3969: ED-only — it reads through the ED service (encounter_type=ED), and the
// Care Coordination modal is driven by the ED store (`useEdCareCoordinationActivities`).
export const useTriageCareCoordination = defineStore(
    'triageCareCoordination',
    () => {
        const list = ref([])
        const loading = ref(false)
        const error = ref(null)

        // pagination
        const total = ref(0)
        const page = ref(1)
        const perPage = ref(10)

        // sorting — default: Created Date, newest first
        const sortBy = ref('created_at')
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
                    // AC13: never completed activities, whatever (if anything) is picked in "Status".
                    status: activeFilters.value.status || OPEN_STATUS,
                },
            })

            try {
                const res =
                    await edCareCoordinationActivitiesService.getAll(query)
                list.value = res.data
                total.value = res.meta.total
                page.value = res.meta.current_page
                perPage.value = res.meta.per_page
            } catch (e) {
                error.value =
                    e?.response?.data?.message ?? 'Failed to load data'
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

        // AC9: notes are tracker notes (AOC-3490), and an activity links to its encounter, not a tracker —
        // resolve the encounter's tracker from the activity's own payload first. A tracker that has since been
        // Closed/Completed no longer accepts notes (HospitalizationController::storeTrackerNote()), so nothing opens.
        async function openCreateNote(row) {
            const activity = await edCareCoordinationActivitiesService.show(
                row.id,
            )
            if (
                !activity?.hospitalization_tracker_id ||
                !isNotesEligible(activity.tracker_status)
            ) {
                return false
            }

            useHospitalizationTracker().openCreateNoteForTracker(
                activity.hospitalization_tracker_id,
            )
            return true
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
            openCreateNote,
        }
    },
)
