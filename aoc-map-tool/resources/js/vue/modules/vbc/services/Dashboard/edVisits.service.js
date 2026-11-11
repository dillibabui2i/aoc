import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const EdVisitsService = {
    // Triage dashboard "ED Visits" bucket endpoint — In Progress, Ready for Review and Ready for Next Steps trackers only.
    getAll: (params) =>
        client
            .get(route('hospitalization.ed-visits.data'), { params })
            .then((r) => r.data),
}
