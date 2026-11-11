import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

// Four filter stores (Hospitalization/Care Coordination/Communication buckets + All Hospitalizations
// page) all read from this one endpoint on mount. It's the same reference data (states/branches/
// regions/statuses) regardless of caller, so the in-flight/resolved request is cached and shared
// instead of firing once per store — otherwise mounting the ICM dashboard alone fires it 3 times.
// The payload differs per bucket (FilterBucketEnum on the backend: the All Immunizations page asks for
// 'IMMUNIZATION'), so each bucket gets its own cache slot; callers that pass no bucket share the default one.
const optionsPromises = new Map()

// EncounterTypeEnum::IP on the backend — the Hospitalization bucket's encounter type (as opposed to
// ED_ENCOUNTER_TYPE, the ED Visits bucket's). Shared here because three stores send it (edVisits.store.js's
// own ED_ENCOUNTER_TYPE stays local — only this one is currently duplicated across callers).
export const HOSPITALIZATION_ENCOUNTER_TYPE = 'IP'

export const FiltersService = {
    getOptions: (bucket = null) => {
        if (!optionsPromises.has(bucket)) {
            const request = bucket
                ? client.get(route('filters.index'), { params: { bucket } })
                : client.get(route('filters.index'))

            optionsPromises.set(
                bucket,
                request.then((r) => r.data).catch((e) => {
                    optionsPromises.delete(bucket)
                    throw e
                })
            )
        }

        return optionsPromises.get(bucket)
    },
}

// Backend returns heterogeneous shapes per group ({value,text} vs {value,label}); normalize to {value,text}.
// `state` is kept when present (branches/regions) so the FE can narrow those lists by the selected State.
export function toOptionList(items) {
    return (items ?? []).map((item) => ({ value: item.value, text: item.text ?? item.label, state: item.state }))
}

// Shared query-param mapping for the three ICM dashboard buckets (Hospitalization, Care Coordination,
// Follow-up) — their read repositories accept the same base filter contract
// (state/branch_ids/region_ids/assignee_ids/me_mode/search/page/per_page/sort_by/sort_dir); only the
// bucket-specific status/date filters differ and are passed in via `extra`.
export function buildBucketQueryParams({ filters, page, perPage, sortBy, sortDir, extra = {} }) {
    const params = {
        page,
        per_page: perPage,
        sort_by: sortBy,
        sort_dir: sortDir,
        me_mode: filters.me_mode || undefined,
        state: filters.state,
        branch_ids: filters.branch,
        region_ids: filters.region,
        assignee_ids: filters.assignee,
        search: filters.search,
        ...extra,
    }

    return Object.fromEntries(
        Object.entries(params).filter(
            ([, v]) => v !== '' && v !== null && v !== undefined && !(Array.isArray(v) && v.length === 0)
        )
    )
}
