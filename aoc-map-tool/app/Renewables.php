<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\RenewablesExpirationReminderMail1;
use App\Mail\RenewablesExpirationReminderMail2;
use App\Mail\RenewablesExpirationReminderMail3;
use App\Notes;
use App\Helpers\H;
use App\Helpers\Notifications;
use DB;

class Renewables extends Model
{
    use HasFactory;
    protected $table = 'renewables';
    protected static $renewables_type = [46, 52, 21];
    protected static $daysarray = [30,15,10,5,3,2,1];

    public static function sentRenewablesMail()
    {
        DB::table('renewables')
            ->select('renewables.*',
            DB::raw('(CASE
                WHEN mssql_providers.IsOfficeStaff = 1 THEN mssql_providers.AOC_Email
                ELSE mssql_providers.External_Email
                END) AS clinicianemail'),
                DB::raw('CONCAT(mssql_providers.FirstName," ",IF(mssql_providers.MiddleName != "",CONCAT(mssql_providers.MiddleName," "),""),mssql_providers.LastName) as name'),
                'renewables_types.name AS renewablename',
                DB::raw('DATEDIFF(expiration_date, CURDATE()) AS days_difference'),
                DB::raw("(SELECT users.id from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as oc"),
                DB::raw("(SELECT u.id from users u left join provider_patient pp on pp.aoc_provider_id = mssql_providers.id left join mssql_patients p on pp.aoc_patient_id = p.id where u.id = p.ccc_id AND pp.last_schedule_date >= '".date('Y-m-d')."' limit 1) as ccc"),
                DB::raw("(SELECT users.id from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Regional Director of Operations' and users.status='1' and find_in_set(mssql_facilities.branchid, users.branchid) limit 1) as rdo"),
                'renewable_rules.Expiring',
                'renewable_rules.email_to_nurse'
            )
            ->join('mssql_providers', 'renewables.aoc_provider_id', '=', 'mssql_providers.id')
            ->join('renewables_types', 'renewables.renewables_type', '=', 'renewables_types.renewable_type_id')
            ->leftJoin("mssql_facilities", "mssql_facilities.FacilityID", "=", "mssql_providers.FacilityID")
            ->leftJoin("renewable_rules", "renewable_rules.renewable_type_id", "=", "renewables_types.id")
            ->whereRaw("FIND_IN_SET(mssql_facilities.State,renewable_rules.states)")
            ->whereRaw("(FIND_IN_SET(mssql_providers.DisciplineName,renewable_rules.discipline) OR renewable_rules.discipline = 'ALL STAFF')")
            ->where(function ($query) {
                $query->orWhere(function ($subquery) {
                    $subquery->where('expiration_date', '<=', DB::raw('DATE_ADD(CURDATE(), INTERVAL 120 DAY)'));
                    $subquery->where('expiration_date', '>=', DB::raw('DATE_SUB(CURDATE(), INTERVAL 1 DAY)'));
                });
            })->where(DB::raw("IFNULL(outcome,'')"), '!=', 'Uploaded to KanTime')
            ->where('mssql_providers.is_deleted',0)
			->whereIn('mssql_providers.EmploymentStatusID', config('constant.renewableClinicianStatusIds'))
			->where('renewables.IsMissing','<>',1)
            ->orderBy('days_difference', 'asc')
            ->chunk(100, function ($renewables) {
                foreach ($renewables as $renewabledetail) {
                    $daysDifference = $renewabledetail->days_difference;
                    if ($daysDifference >= 0 && $daysDifference <= 15) {
                        self::createNotification($renewabledetail, 'daily');
                    }
                    $status = $renewabledetail->email_notification_status;
                    $temp = '';
                    $dayCount = 0;
                    $tempName = '';
                    if(($renewabledetail->Expiring == 1) && ($renewabledetail->email_to_nurse == 1)){
                        if ($daysDifference <= 120 && $daysDifference > 90 && is_null($status)) {
                            $tempName = 'RenewablesExpirationReminderMail1';
                            $dayCount = 120;
                            $temp = new RenewablesExpirationReminderMail1($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 90 && $daysDifference > 60 && $status != 90) {
                            $tempName = 'RenewablesExpirationReminderMail1';
                            $dayCount = 90;
                            $temp = new RenewablesExpirationReminderMail1($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 60 && $daysDifference > 45 && $status != 60) {
                            $tempName = 'RenewablesExpirationReminderMail1';
                            $dayCount = 60;
                            $temp = new RenewablesExpirationReminderMail1($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 45 && $daysDifference > 30 && $status != 45) {
                            $tempName = 'RenewablesExpirationReminderMail1';
                            $dayCount = 45;
                            $temp = new RenewablesExpirationReminderMail1($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 30 && $daysDifference > 15 && $status != 30) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 30;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 15 && $daysDifference > 10 && $status != 15) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 15;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 10 && $daysDifference > 5 && $status != 10) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 10;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference <= 5 && $daysDifference > 3 && $status != 5) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 5;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference == 3 && $status != 3) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 3;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference == 2 && $status != 2) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 2;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference == 1 && $status != 1) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = 1;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        } elseif ($daysDifference == -1 && $status != 1) {
                            $tempName = 'RenewablesExpirationReminderMail2';
                            $dayCount = -1;
                            $temp = new RenewablesExpirationReminderMail2($renewabledetail, $dayCount);
                        }
                    }
                    if (empty($temp)) {
                        continue;
                    }
                    H::ExcelLog(',"Nurse": ' . $renewabledetail->name . ', Email: '. $renewabledetail->clinicianemail .', Renewable: ' . $renewabledetail->renewablename . ', Expiration Date: ' . H::getFormatedDate($renewabledetail->expiration_date) .', EmailTemplate: '.$tempName. ', URL: ' . route('renewabledocument.upload', ['id' => base64_encode($renewabledetail->id)]),'testRenewableEmails.log');


                    try{
                        Mail::to($renewabledetail->clinicianemail)->send($temp);
                        $note = [
                            "notes" => 'Automated Email Reminder - Expiring ' . $renewabledetail->renewablename . ' sent on ' . H::getFormatedDate(date('m/d/Y')) . '',
                            "entity" => 'clinician',
                            "entity_id" => $renewabledetail->aoc_provider_id,
                            "misc1" => $dayCount
                        ];
                        Notes::create($note);
                        self::createNotification($renewabledetail);
                        $id = $renewabledetail->id;
                        DB::table('renewables')->where('id', $id)->update(['email_notification_status' => $dayCount]);
                        H::ExcelLog("Email is sent to: " . $renewabledetail->clinicianemail. ", Clinician: ".$renewabledetail->name.", RenewableName :".$renewabledetail->renewablename." on ".H::getFormatedDate(date('m-d-Y'))." for day count ".$dayCount."", 'renewablesmail.log');
                        H::ExcelLog("Note is created for this: " . $renewabledetail->clinicianemail. " on ".H::getFormatedDate(date('m-d-Y'))." for ".$dayCount."", 'renewablesmail.log');
                    }catch(\Exception $e){
                        H::ExcelLog($e->getMessage() ."Provider name". $renewabledetail->renewablename , 'renewablesmail.log');
                    }
                }
            });
    }

    public static function createNotification($renewabledetail, $type = NULL)
    {
        $daysDifference = $renewabledetail->days_difference;
        $data = [];
        if(($daysDifference >= 0 && $daysDifference <= 15) && $type == 'daily'){
            $ProviderID = $renewabledetail->ProviderID;
            $result = DB::select("
            SELECT CONCAT(GROUP_CONCAT(DISTINCT CONCAT(Patient_FirstName, ' ', Patient_LastName))) as PatientData
            FROM mssql_schedulemaster sm
            LEFT JOIN mssql_patients p ON sm.PatientID = p.PatientID
            WHERE sm.ProviderID = ?
            AND sm.ScheduleDate >= ?", [$ProviderID, date('Y-m-d')]);
            $string = '';
            $count = count($result);
            foreach ($result as $key => $pdata) {
                $string .= $pdata->PatientData;
                if ($key < $count - 1) {
                    $string .= ',';
                }
            }
            if(!empty($string)){
                if($renewabledetail->ccc != null){
                    $dataccc = [
                        "related_item" => $renewabledetail->id,
                        "type" => 'renewable',
                        "title" => "" . $renewabledetail->name . "'s Expiring " . $renewabledetail->renewablename . "- Action Required for ".$string,
                        "notes" => "Clinician is at risk of being out of compliance and blocked from scheduling for ".$string.". Please call the clinician in attempt to retrieve their expiring renewable item before the expiration date.",
                        "user_id" => $renewabledetail->ccc,
                    ];
                    Notifications::addNotification($dataccc);
                }
                if($renewabledetail->rdo != null){
                    $datardo = [
                        "related_item" => $renewabledetail->id,
                        "type" => 'renewable',
                        "title" => "" . $renewabledetail->name . "'s Expiring " . $renewabledetail->renewablename . "- Action Required for ".$string,
                        "notes" => "Clinician is at risk of being out of compliance and blocked from scheduling for ".$string.". Please call the clinician in attempt to retrieve their expiring renewable item before the expiration date.",
                        "user_id" => $renewabledetail->rdo,
                    ];
                    Notifications::addNotification($datardo);
                }
            }
        }else{
            if(in_array($renewabledetail->renewables_type, self::$renewables_type)){
                if($daysDifference <= 30){
                    $data = [
                        "related_item" => $renewabledetail->id,
                        "type" => 'renewable',
                        "title" => "" . $renewabledetail->name . "'s Expiring " . $renewabledetail->renewablename . "- Action Required",
                        "notes" => "Please call the clinician in attempt to retrieve their expiring renewable item and update their KanTime profile.",
                        "user_id" => $renewabledetail->oc,
                    ];
                }
            }else{
                if(in_array($daysDifference, self::$daysarray)){
                    $data = [
                        "related_item" => $renewabledetail->id,
                        "type" => 'renewable',
                        "title" => "" . $renewabledetail->name . "'s Expiring " . $renewabledetail->renewablename . "- Action Required",
                        "notes" => "Please update the Clinician's expiring renewable item in their KanTime profile.",
                        "user_id" => $renewabledetail->oc,
                    ];
                }
            }
        }
        if (isset($data['related_item'])) {
            Notifications::addNotification($data);
            H::ExcelLog("Notification is created for this: " . $renewabledetail->clinicianemail. " on ".H::getFormatedDate(date('m-d-Y'))."", 'renewablesmail.log');
        }
    }
}
