<?php

namespace App\Enums\CCR;

enum OutcomeStatusEnum: string
{
    case CANDIDATE_ESTABLISHED_WITH_CLIENT = 'Candidate already established with client';
    case COMPLETED = 'Completed';
    case CANCELLED = 'Cancelled';
    case ATTEMPT1 = 'Attempt 1';
    case ATTEMPT2 = 'Attempt 2';
    case ATTEMPT3 = 'Attempt 3';
}
