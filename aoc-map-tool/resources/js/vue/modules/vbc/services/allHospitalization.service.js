import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const AllHospitalizationService = {
    getAll: (params) => client.get(route('hospitalization.all-data'), {params}).then(r => r.data),
    export: (params) => client.get(route('hospitalization.all-data.export'), {params, responseType: 'blob'}),
}
