import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const CommunicationService = {
    // The Communication bucket is fed by follow-ups.
    getAll: (params) => client.get(route('follow-up.index'), {params}).then(r => r.data),
}
