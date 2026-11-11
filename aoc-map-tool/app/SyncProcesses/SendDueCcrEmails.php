<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class SendDueCcrEmails
{

    public static function processSendDueCcrEmails()
    {
        $bucket = DB::table('recruiting_schedule');
        $bucket->select(
            'recruiting_schedule.outcome',
            'recruiting_schedule.scheduled_date',
            "recruiting_schedule.tracker_id",
            'mssql_recruitertracker.Candidate',
            'recruiting_schedule.id',
            'mssql_patients.PatientAccount',
            'users.name',
            'users.email',
            DB::raw("users.id AS user_id"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            DB::raw("(SELECT name from users where id = recruiting_schedule.created_by) AS requested_by"),
            "mssql_patients.CF_Specialized as client_specialized",
            DB::raw("CASE WHEN mssql_recruitertracker.aoc_candidate_id != NULL THEN candidates.homePhone
        WHEN mssql_recruitertracker.aoc_provider_id != NULL THEN mssql_providers.Phone
        END as display_phone"),
            DB::raw("CASE WHEN mssql_recruitertracker.aoc_candidate_id != NULL THEN  candidates.mobile
        WHEN mssql_recruitertracker.aoc_provider_id != NULL THEN mssql_providers.Mobile
        END as candidate_mobile"),
            'recruiting_schedule.appointment_date',
            'mssql_recruitertracker.created_at',
            'mssql_recruitertracker.proposed_next_step',
            DB::raw("DATE_FORMAT(CONCAT(mssql_recruitertracker.proposed_ns_date,' ',mssql_recruitertracker.proposed_ns_time), '%m/%d/%Y %H:%i %p') AS proposed_next_step_date"),
            'mssql_recruitertracker.Candidate_Start_Date',
            'mssql_recruitertracker.assigned_ccr',
            'mssql_patients.ccm_id'
        );
        $bucket->leftJoin("mssql_patients", function ($query) {
            $query->on("mssql_patients.id", "=", "recruiting_schedule.aoc_patient_id");
        });
        $bucket->leftJoin("mssql_recruitertracker", function ($query) {
            $query->on("mssql_recruitertracker.id", "=", "recruiting_schedule.tracker_id");
        });
        $bucket->leftJoin("mssql_providers", function ($query) {
            $query->on("mssql_providers.id", "=", "mssql_recruitertracker.aoc_provider_id");
        });
        $bucket->leftJoin("candidates", function ($query) {
            $query->on("candidates.id", "=", "mssql_recruitertracker.aoc_candidate_id");
        });
        $bucket->leftJoin("users", function ($query) {
            $query->on("users.id", "=", DB::raw("IF(mssql_recruitertracker.assigned_ccr <> '' , mssql_recruitertracker.assigned_ccr, mssql_patients.ccm_id)"));
        });
        $bucket->leftJoin("mssql_facilities", function ($query) {
            $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
        });
        $bucket->where("recruiting_schedule.type_id", '=', 2);
        $bucket->whereRaw("DATE_ADD(CONCAT(recruiting_schedule.appointment_date,' ',recruiting_schedule.appointment_time),INTERVAL -24 HOUR) < NOW()");
        $bucket->where("recruiting_schedule.outcome", '<>', 'Unable to contact');
        $bucket->where("recruiting_schedule.outcome", '!=', 'Completed');
        $bucket->where("mssql_recruitertracker.Candidate_Current_Status", '<>', 'Inactive');
        $bucket->where('recruiting_schedule.created_at', '>', '2023-02-01');
        $bucket->where("recruiting_schedule.due_ccr_notification", 0);
        $dueCcrRequestList = $bucket->get();
        $dueCcrList = H::objToArr($dueCcrRequestList);
        if (count($dueCcrList) > 0) {
            $emailInnerBody = '';
            foreach ($dueCcrList as $dueCcrData) {
                $emailInnerBody = 'You have a pending CCR request for ' . $dueCcrData['patient_name'] . ' that is due on ' . $dueCcrData['appointment_date'] . '. Please complete ASAP. If you are unable to complete prior to the due date please reach out to your manager.<br>';
                $emailInnerBody = $emailInnerBody . 'Candidate: ' . $dueCcrData['Candidate'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Client: ' . $dueCcrData['patient_name'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Specialized: ' . $dueCcrData['client_specialized'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Requested By: ' . $dueCcrData['requested_by'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Request Date: ' . $dueCcrData['scheduled_date'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Due Date: ' . $dueCcrData['appointment_date'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Phone Number: ' . $dueCcrData['display_phone'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Mobile: ' . $dueCcrData['candidate_mobile'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Proposed Next Step: ' . $dueCcrData['proposed_next_step'] . '<br>';
                $emailInnerBody = $emailInnerBody . 'Next Step Date: ' . $dueCcrData['proposed_next_step_date'] . '<br>';
                if ($dueCcrData['user_id'] == '') {
                    H::ExcelLog('Blank User id=' . $dueCcrData['id'], 'processSendDueCcrEmails.log');
                    $userDetails = DB::table('users')->where('id', 15)->first();
                    $to = $userDetails->email;
                } else {
                    $to = $dueCcrData['email'];
                }
                $subject = 'Scheduled CCR';
                $body = $emailInnerBody;

                H::getHelper('EmailNotification')->sendEmailsWithoutAttach($to, $subject, $body, 'html');
                DB::table('recruiting_schedule')->where('id', $dueCcrData['id'])->update(['due_ccr_notification' => 1]);
            }
        }
    }
}
