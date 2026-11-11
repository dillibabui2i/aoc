// The module's stylesheet, loaded once here rather than per component — see
// styles/vbc.scss for why it is global.
import '@/modules/vbc/styles/vbc.scss'

import AllHospitalizationSection from '@/modules/vbc/sections/AllHospitalizationSection.vue'
import AllImmunizationSection from '@/modules/vbc/sections/AllImmunizationSection.vue'
import HospitalizationBucketSection from '@/modules/vbc/sections/Dashboard/HospitalizationBucketSection.vue'
import CareCoordinationActivitiesBucketSection from '@/modules/vbc/sections/Dashboard/CareCoordinationActivitiesBucketSection.vue'
import CommunicationBucketSection from '@/modules/vbc/sections/Dashboard/CommunicationBucketSection.vue'
import EdVisitsBucketSection from '@/modules/vbc/sections/Dashboard/EdVisitsBucketSection.vue'
import TriageCareCoordinationBucketSection from '@/modules/vbc/sections/Dashboard/TriageCareCoordinationBucketSection.vue'
import ImmunizationBucketSection from '@/modules/vbc/sections/Dashboard/ImmunizationBucketSection.vue'
import ImmunizationScheduleSection from '@/modules/vbc/sections/ImmunizationScheduleSection.vue'

export default [
    { selector: '#vue-vbc', component: AllHospitalizationSection },
    { selector: '#vue-vbc-immunization', component: AllImmunizationSection },
    { selector: '#vbc-bucket-hospitalization', component: HospitalizationBucketSection },
    { selector: '#vbc-bucket-care-coordination', component: CareCoordinationActivitiesBucketSection },
    { selector: '#vbc-bucket-communication', component: CommunicationBucketSection },
    // Triage dashboard buckets (PD-4057)
    { selector: '#vbc-bucket-ed-visits', component: EdVisitsBucketSection },
    { selector: '#vbc-bucket-triage-care-coordination', component: TriageCareCoordinationBucketSection },
    { selector: '#vbc-bucket-immunization', component: ImmunizationBucketSection },
    { selector: '#vue-vbc-immunization-schedule', component: ImmunizationScheduleSection },
]
