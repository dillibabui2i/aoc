<?php

namespace App\Enums;

enum JobViteRecruiterJobType: string
{
    case NEW = 'NEW';
    case RECRUITER_PHONE_SCREEN = 'recruiter_phone_screen';
    case JPET = 'jpet';
    case PENDING_GRADUATION_FOLLOWUP = 'pending_graduation_followup';
    case NEW_THERAPY = 'NEW_therapy';
    case RECRUITER_PHONE_SCREEN_THERAPY = 'recruiter_phone_screen_therapy';
    case SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY = 'scheduled_recruiter_phone_screen_therapy';
    case JPET_THERAPY = 'jpet_therapy';
    case PENDING_GRADUATION_FOLLOWUP_THERAPY = 'pending_graduation_followup_therapy';
}
