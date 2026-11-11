<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case AFTER_HOURS_TYPE = 'AfterHours';
    case AFTER_HOURS_CANCELED_TYPE = 'AfterHoursCanceled';
    case OFFICE_EVAL_TYPE = 'office_eval';
    case OFFICE_EVAL_VIEW_TYPE = 'office_eval_view';
    case OFFICE_EVAL_EDIT_TYPE = 'office_eval_edit';
    case STATUS_CHANGE_TYPE = 'statusChange';
    case RENEWABLE_TYPE = 'renewable';
    case CLINICIAN_TYPE = 'Clinician';
    case JOBS_TYPE = 'jobs';
    case RENEWABLES_TYPE = 'renewables';
    case NTH_ACCOUNT_TYPE = 'nthAccount';
    case STATUS_CHANGE_REQUEST_TYPE = 'statusChangeRequest';
    case WIND_DOWN_HOUR_TYPE = 'wind-down-hour';
    case SUPPLY_REQUESTS_TYPE = 'supplyRequests';
    case CLIENT_MATCH_TYPE = 'ClientMatch';
    case COVERAGE_TYPE = 'coverage';
    case BILLING_TYPE = 'billing';
    case TRACKER_ACTIVITY_TYPE = 'tracker-activity';
    case COVERAGE_DETAILS_TYPE = 'coverageDetails';
    case INTAKE_TYPE = 'intake';
    case JOBVITE_TYPE = 'jobvite';
    case TRIAGE_CALL_TYPE = 'triageCall';
    case INVOICE_TYPE = 'invoice';
    case NTH_SCHEDULE_CHANGE_TYPE = 'nthScheduleChange';
}
