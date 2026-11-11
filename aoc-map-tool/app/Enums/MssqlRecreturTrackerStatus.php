<?php

namespace App\Enums;

/**
 * Values of `mssql_recruitertracker.Candidate_Current_Status`.
 */
enum MssqlRecreturTrackerStatus: string
{
    case NEE_TO_REVIEW_NEW_CLIENTS = 'Need to Review New Clients';
    case ADDED_TO_POTENTIAL_NURSE_LIST = 'Added to Potential Nurse List';
    case BONUS_PAID = 'Bonus Paid';
    case CANT_MATCH_PAY = "Can't Match Pay";
    case CLIENT_MATCH = 'Client Match';
    case CLINICAL_COMPETENCY_REVIEW = 'Clinical Competency Review';
    case CURRENT_NURSE_PLACED = 'Current Nurse Placed';
    case DELETED = 'Deleted';
    case DO_NOT_USE = 'Do Not Use';
    case FIRST_SHIFT_CONFIRMED = 'First Shift Confirmed';
    case HIRED = 'Hired';
    case HIRED_PRN = 'Hired - PRN';
    case INACTIVE = 'Inactive';
    case INELIGIBLE_FOR_HIRE = 'Ineligible for Hire';
    case INELIGIBLE_FOR_HIRE_REHIRE = 'Ineligible for Hire/Rehire';
    case IPEA_CANCELLED = 'IPEA - Cancelled';
    case IPEA_NC_NS = 'IPEA - NC/NS';
    case IPEA_PENDING_ORIENTATION = 'IPEA - Pending Orientation';
    case IPEA_RESCHEDULED = 'IPEA - Rescheduled';
    case IPEA_SCHEDULED = 'IPEA - Scheduled';
    case NEED_TO_BE_SCHEDULED_BY_RA = 'Need to be Scheduled by RA';
    case NEED_TO_SCHEDULE_IPEA = 'Need to schedule IPEA';
    case NEW_NURSE_PLACED = 'New Nurse Placed';
    case NOT_A_MATCH = 'Not a Match';
    case PENDING_START = 'Pending Start';
    case PEPI_CANCELLED = 'PEPI - Cancelled';
    case PEPI_COMPLETED = 'PEPI - Completed';
    case PEPI_NC_NS = 'PEPI - NC/NS';
    case PEPI_SCHEDULED = 'PEPI - Scheduled';
    case POTENTIAL_CANDIDATE = 'Potential Candidate';
    case PRN = 'PRN';
    case PROSPECT = 'Prospect';
    case READY_FOR_ACTIVE_COVERAGE = 'Ready for Active Coverage';
    case REHIRE = 'Rehire';
    case STANDBY = 'Standby';
    case TRAINING_SHIFT_SCHEDULED = 'Training Shift Scheduled';
    case WEPI_CANCELLED = 'WEPI - Cancelled';
    case WEPI_SCHEDULED = 'WEPI - Scheduled';
}
