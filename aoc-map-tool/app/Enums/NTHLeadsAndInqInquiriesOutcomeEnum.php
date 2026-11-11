<?php

namespace App\Enums;

enum NTHLeadsAndInqInquiriesOutcomeEnum: string
{
    case ATTEMPT1 = 'Attempt 1';
    case ATTEMPT2 = 'Attempt 2';
    case ATTEMPT3 = 'Attempt 3';
    case SCHEDULE_NEXT_STEPS = 'Schedule Next Steps';
    case SUGGEST_OTHER_CLIENTS = 'Suggest Other Clients';
    case RESOLVED = 'Resolved';
}
