import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const HospitalizationService = {
    // ICM dashboard bucket endpoint — active trackers only (see routes.php).
    getAll: (params) => client.get(route('hospitalization.data'), {params}).then(r => r.data),
}
