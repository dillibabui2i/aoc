<?php

namespace App\Enums;

enum JobviteTherapyOutcomeEnum: string
{
    case NEW = 'New';
    case RECRUITER_PHONE_SCREEN = 'Recruiter Phone Screen';
    case SCHEDULED_RECRUITER_PHONE_SCREEN = 'Scheduled Recruiter Phone Screen';
    case SCHEDULED_CLINICAL_INTERVIEW = 'Scheduled Clinical Interview';
    case RESCHEDULED_CLINICAL_INTERVIEW = 'Rescheduled Clinical Interview';
    case CLINICAL_INTERVIEW = 'Clinical Interview';
    case OFFER_EXTENDED = 'Offer Extended';
    case OFFER_ACCEPTED = 'Offer Accepted';
    case OFFER_REJECTED = 'Offer Rejected';
    case REJECTED_BY_AGENCY = 'Rejected by Agency';
    case DO_NOT_CONTACT_DO_NOT_HIRE = 'Do not contact/do not hire';
    case PET_1 = 'P.E.T. #1';
    case PET_2 = 'P.E.T. #2';
    case PET_3 = 'P.E.T. #3';
    case THERAPIST_STUDENT_PENDING_GRADUATION = 'Therapist Student: Pending Graduation';
}
