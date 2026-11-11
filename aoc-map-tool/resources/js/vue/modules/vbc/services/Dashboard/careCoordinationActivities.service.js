import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

// PD-3969: this is the ICM/IP flow — every call states `encounter_type=IP`, so ED trackers are neither
// listed nor offered here (the ED flow has its own copy, edCareCoordinationActivities.service.js).
export const IP_ENCOUNTER_TYPE = 'IP'

export const careCoordinationActivitiesService = {
    // `getAll`'s `CareCoordinationCollection` builds its own `{data, meta}` shape manually, so `r.data` is
    // the whole thing already. `show`/`create`/`update`'s `CareCoordinationResource` is a single-item
    // JsonResource, wrapped by Laravel's default `{"data": {...}}` envelope — needs the extra `.data`,
    // same as `HospitalizationTrackerService`'s `.then((r) => mirrorTracker(r.data.data))`.
    getAll: (params) =>
        client
            .get(route('care-coordination.index'), {
                params: { ...params, encounter_type: IP_ENCOUNTER_TYPE },
            })
            .then(r => r.data),
    show: (id) => client.get(route('care-coordination.show', { id })).then((r) => r.data.data),
    create: (payload) =>
        client
            .post(route('care-coordination.store'), { ...payload, encounter_type: IP_ENCOUNTER_TYPE })
            .then((r) => r.data.data),
    update: (id, payload) =>
        client
            .patch(route('care-coordination.update', { id }), { ...payload, encounter_type: IP_ENCOUNTER_TYPE })
            .then((r) => r.data.data),
    searchEligibleClients: (search) =>
        client
            .get(route('care-coordination.clients.index'), {
                params: { search, encounter_type: IP_ENCOUNTER_TYPE },
            })
            .then((r) => r.data.data),
    getTypes: () => client.get(route('care-coordination.types.index')).then((r) => r.data),
}
