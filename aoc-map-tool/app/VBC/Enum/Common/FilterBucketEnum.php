<?php

namespace App\VBC\Enum\Common;

/** Which page/bucket is asking for filter options — see FiltersService::getOptions(). */
enum FilterBucketEnum: string
{
    case HOSPITALIZATION = 'HOSPITALIZATION';
    case CARE_COORDINATION = 'CARE_COORDINATION';
    case FOLLOW_UP = 'FOLLOW_UP';
    case IMMUNIZATION = 'IMMUNIZATION';
    case IMMUNIZATION_SCHEDULE = 'IMMUNIZATION_SCHEDULE';
    case ED_VISITS = 'ED_VISITS';
}
