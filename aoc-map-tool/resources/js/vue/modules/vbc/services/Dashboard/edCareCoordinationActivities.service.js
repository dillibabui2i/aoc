import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

// PD-3969: ED copy of careCoordinationActivities.service.js. Same endpoints, but every call states the
// encounter type (`ED`) so the backend scopes the list/search and guards create/update by it.
export const ED_ENCOUNTER_TYPE = 'ED'

export const edCareCoordinationActivitiesService = {
    // `getAll`'s `CareCoordinationCollection` builds its own `{data, meta}` shape, so `r.data` is the
    // whole thing; `show`/`create`/`update` are single-item JsonResources wrapped in `{"data": {...}}`.
    getAll: (params) =>
        client
            .get(route('care-coordination.index'), {
                params: { ...params, encounter_type: ED_ENCOUNTER_TYPE },
            })
            .then((r) => r.data),
    show: (id) => client.get(route('care-coordination.show', { id })).then((r) => r.data.data),
    create: (payload) =>
        client
            .post(route('care-coordination.store'), { ...payload, encounter_type: ED_ENCOUNTER_TYPE })
            .then((r) => r.data.data),
    update: (id, payload) =>
        client
            .patch(route('care-coordination.update', { id }), { ...payload, encounter_type: ED_ENCOUNTER_TYPE })
            .then((r) => r.data.data),
    searchEligibleClients: (search) =>
        client
            .get(route('care-coordination.clients.index'), {
                params: { search, encounter_type: ED_ENCOUNTER_TYPE },
            })
            .then((r) => r.data.data),
    getTypes: () => client.get(route('care-coordination.types.index')).then((r) => r.data),
}
