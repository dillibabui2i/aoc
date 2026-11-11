import client from '@/core/api/client.js'
import { route } from 'ziggy-js'

export const ImmunizationBucketService = {
    getAll: (params) => client.get(route('immunization.bucket-data'), { params }).then((r) => r.data),
}
