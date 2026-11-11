<?php

namespace App\Enums;

enum MssqlEmploymentStatus: int
{
    case ACTIVE = 1;
    case STANDBY = 2;
    case INACTIVE = 3;
    case DECEASED = 4;
    case MEDICAL_LEAVE = 5;
    case COVER_NURSE = 6;
    case PENDING_ORIENTATION = 7;
    case PRN = 8;
    case QUIT = 9;
    case DO_NOT_USE = 10;
    case DO_NOT_HIRE = 11;
    case TERMINATED = 12;
    case DO_NOT_CONTACT = 13;
    case PENDING_NPI = 14;
    case TEMPORARY_NURSE = 15;
    case TERMINATE = 16;
    case PENDING_ORIENTATION_DOCUMENTS = 17;
    case PENDING_ORIENTATION_COMPLIANCE = 18;
    case PENDING_NCLEX = 19;
    case RE_HIRE = 20;
    case ON_LEAVE = 21;
    case PRN_AGREEMENT = 22;
    case PENDING_RENEWABLE = 23;
    case ACTIVE_COVERAGE = 24;
    case ACTIVE_ADVOCACY = 25;
    case PPEC_AGREEMENT = 26;
    case OFFICE_ACQUISITION = 27;
    case FIELD_ACQUISITION = 28;
}
