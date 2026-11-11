<?php

namespace App\Enums;

enum PendingOutcomeReason: string
{
    // Pending Clinician Status
    case CLINICIAN_PENDING_ORIENTATION = 'Clinician Pending Orientation';
    case CLINICIAN_ON_STANDBY = 'Clinician on Standby - Status Change Requested';
    case CLINICIAN_PENDING_RENEWABLE = 'Clinician is Pending Renewable - Status Change Requested';

    // Pending Client
    case ADMISSION_NOT_COMPLETED = 'Admission visit not completed';
    case AUTHORIZATION = 'Authorization';
    case HOSPITAL_DISCHARGE = 'Hospital Discharge';
    case ORDERS = 'Orders';
    case PENDING_INITIAL_ORDER = 'Pending Initial Order';
    case POST_HOSPITAL_REASSESSMENT = 'Post Hospital Reassessment';
    case PRIVATE_PAY = 'Private Pay';
    case SCHOOL_SET_UP = 'School Set Up';

    public static function clinician(): array
    {
        return [
            self::CLINICIAN_PENDING_ORIENTATION->value,
            self::CLINICIAN_ON_STANDBY->value,
            self::CLINICIAN_PENDING_RENEWABLE->value,
        ];
    }

    public static function client(): array
    {
        return [
            self::ADMISSION_NOT_COMPLETED->value,
            self::AUTHORIZATION->value,
            self::HOSPITAL_DISCHARGE->value,
            self::ORDERS->value,
            self::PENDING_INITIAL_ORDER->value,
            self::POST_HOSPITAL_REASSESSMENT->value,
            self::PRIVATE_PAY->value,
            self::SCHOOL_SET_UP->value,
        ];
    }
}
