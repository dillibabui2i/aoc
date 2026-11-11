import client from '@/core/api/client.js'
import { route } from 'ziggy-js'
import { FiltersService } from '@/modules/vbc/services/filters.service.js'

// FilterBucketEnum::IMMUNIZATION_SCHEDULE on the backend
const OPTIONS_BUCKET = 'IMMUNIZATION_SCHEDULE'

export const ImmunizationScheduleService = {
    getAll: (params) => client.get(route('immunization.schedule.data'), { params }).then((r) => r.data),
    getOptions: () => FiltersService.getOptions(OPTIONS_BUCKET),
    getOne: (scheduleId) =>
        client
            .get(route('immunization.schedule.show', { scheduleId }))
            .then((r) => r.data.data),
    create: (payload) =>
        client
            .post(route('immunization.schedule.store'), payload)
            .then((r) => r.data.data),
    update: (scheduleId, payload) =>
        client
            .put(route('immunization.schedule.update', { scheduleId }), payload)
            .then((r) => r.data.data),
}
