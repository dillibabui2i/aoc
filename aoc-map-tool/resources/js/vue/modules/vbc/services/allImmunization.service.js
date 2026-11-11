import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const AllImmunizationService = {
    getAll: (params) => client.get(route('immunization.all-data'), {params}).then(r => r.data),
    export: (params) => client.get(route('immunization.all-data.export'), {params, responseType: 'blob'}),
}
