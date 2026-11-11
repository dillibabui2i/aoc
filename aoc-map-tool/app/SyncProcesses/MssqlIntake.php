<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Helpers\Notifications;
use App\Notes;
use App\Services\NeedToHire\SocialMediaAdAutoCloseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class MssqlIntake
{
    public function __construct(
        private readonly SocialMediaAdAutoCloseService $socialMediaAdAutoCloseService
    ) {}

    /**
     * Sync Intake.
     */
    public function syncIntake()
    {
        echo "Sync Intake List \n";
        $syncType = 'intake';
        $mssqlTableName = '[Excelss].[GENERAL_IntakePatients]';
        $tableName = 'mssql_patients';
        $fieldName = 'IntakeID';

        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $limit = $batch_info['batch_size'];
        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }

        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT top ' . $limit . " * FROM $mssqlTableName
			WHERE [$fieldName]  > '$last_updated_id'
			ORDER BY [$fieldName] ASC";
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        $trackingFields = ['PatientAccount','PatientName','Patient_Gender','Patient_Address1','Patient_Address2','Patient_City','Patient_Zip','Patient_State','Patient_Phone','Patient_MobilPhone','Patient_DOB','AuthorizedWeeklyHours','CNAAuthorizedWeeklyHours',
        'CF_RespWeeklyHrs','CF_WorkForceDevProj','PreferenceList','ClientCareCoordinator',
        'CC2','Payer','RespitePayer', 'Patient_CurrentStatusID'];

		$ignoreSyncFromKanTime = ['CF_ClinicianDiscipline','CF_DME','CF_LiftingRequired','CF_MeetingNurses','CF_MultipleClientHome','CF_NurseLanguage','Languages','PetsinHome','PetsNurseCannotOwn','CF_ServicesNeeded','CF_Skills','CF_Specialized','Biography','NTHNotes','CF_TheSale','CF_Weight','CF_Schooldistrict','CF_SchoolName','CF_SharedAgency','PreferenceList'];

        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;

            //figure out Payer Group
            if(strpos($row['CF_Skills'],'Trach')!==false){
                $specialized = 1;
            }else{
                $specialized = 0;
            }
            $payer = $row['Payer'];
            $payer_group = $row['SpecialPayerGroupName'];
            if($payer=='TX- SUPERIOR STARPLUS' && $specialized==1){
                $payer_group = 'SUPERIOR STARPLUS';
            }

            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row[$fieldName];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            echo $uniqueID . "\n";
            $dbRecord = DB::table($tableName)->select('*')->where($fieldName, $uniqueID)->get();
            $originalData = json_decode(json_encode($dbRecord), true);

            //set information to newRow array
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $value = str_replace('§','',$value);
                if(substr($value,-1)==',') {
                    $value = substr($value,0,strlen($value)-1);
                }
                $newKey = str_replace(' ', '_', $key);
				if(in_array($newKey, $ignoreSyncFromKanTime)) {
                    echo 'The value for ' . $newKey . ' ignored to sync from KanTime \n';
                    continue;
                }
                if (Schema::hasColumn($tableName, $newKey)) {
                    if (trim($value)!='') {
                        if(in_array($newKey,$trackingFields)){
                            if(isset($originalData[0][$newKey]) && $originalData[0][$newKey]!=$value){
                                echo 'The value for ' . $newKey . ' changed from "' . $originalData[0][$newKey] . '" to "' . $value . '"' . "\n";
                            }
                        }
                        //$newRow[$newKey] = $value;
                    }
                }
            }


            $newRow['is_updated'] = '1';

            $newRow['payer_group'] = $payer_group;
            $newRow['Payer'] = $payer;
            if (count($dbRecord) > 0) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
                $aoc_patient_id = $dbRecord[0]->id;
                //see if this patient is on NTH and update map if needed

                $nthAccount = DB::table('NeedToHire_Accounts')
                                ->select('id','user_id')
                                ->where('NeedToHire_Accounts.aoc_patient_id',$aoc_patient_id)
                                ->where('NeedToHire_Accounts.IsActive',1)->first();
                if(isset($nthAccount->id)){
                    $nthAccountId = $nthAccount->id;

                    //see if the patient has been discharged or non-admit and disable on NTH and map
                    if($row['Patient_CurrentStatusID']==3 || $row['Patient_CurrentStatusID']==5){
                        DB::table('NeedToHire_Accounts')->where('id', $nthAccountId)->update(['IsActive'=>0]);
                        $this->socialMediaAdAutoCloseService->closeOpenAdsForNth($nthAccountId);
                        //send Recruiter and CCC a notification and record as note
                        $notificationText = 'NTH Account for ' . $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' (' . $row['PatientAccount'] . ') was set to Inactive because Patient Status is Non-Admit or Discharged.';
                        if(isset($nthAccount->user_id) && $nthAccount->user_id!=0 && $nthAccount->user_id!=''){
                            Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                            Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                        }
                        if(isset($newRow['ccc_id']) && $newRow['ccc_id']!=0 && $newRow['ccc_id']!=''){
                            Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                            Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                        }
                        echo $notificationText . ' notification sent to admin' . "\n";
                        $insert = Notes::insert(['entity' => 'NeedToHire', 'entity_id' => $nthAccountId, 'nth_id' => $nthAccountId,
                        'notes' => $notificationText, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                    }
                    //now update map (which will also be disabled for 3 & 5)
                }else{
                    Log::info('No NTH found', ['aoc_patient_id' => $aoc_patient_id]);
                }

            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
       DB::statement("update intake
                        left join mssql_patients on mssql_patients.id = intake.aoc_patient_id
                        left join mssql_providers on mssql_providers.ClinicianID = CAST(mssql_patients.SOCClinicianID AS CHAR)
                        set intake.IntakeStatus = 'Admitted'
                        where
                            intake.LOBID IN ('1003', '1004', '1006') AND
                            IFNULL(
                            (
                                select count(*) as count from mssql_schedulemaster where
                                mssql_schedulemaster.ProviderID = mssql_providers.ProviderID and
                                mssql_schedulemaster.PatientID = mssql_patients.PatientID and
                                mssql_schedulemaster.ScheduleStatusID = 5 AND
                                mssql_schedulemaster.ServiceCodeID IN (
                                    select service_id from service_codes_daas WHERE name IN
                                    (
                                        'PT Initial Evaluation - Acute',
                                        'PT Initial Evaluation - Acute EI',
                                        'PT Initial Evaluation - Long Term',
                                        'PT Initial Evaluation - Long Term EI',
                                        'PT SOC Initial Evaluation - Acute',
                                        'PT SOC Initial Evaluation - Acute - OASIS',
                                        'PT SOC Initial Evaluation - Acute EI',
                                        'PT SOC Initial Evaluation - Long Term',
                                        'PT SOC Initial Evaluation - Long Term - OASIS',
                                        'PT SOC Initial Evaluation - Long Term EI',
                                        'OT Initial Evaluation - Acute',
                                        'OT Initial Evaluation - Acute EI',
                                        'OT Initial Evaluation - Long Term',
                                        'OT Initial Evaluation - Long Term EI',
                                        'OT SOC Initial Evaluation - Acute',
                                        'OT SOC Initial Evaluation - Acute - OASIS',
                                        'OT SOC Initial Evaluation - Acute EI',
                                        'OT SOC Initial Evaluation - Long Term',
                                        'OT SOC Initial Evaluation - Long Term - OASIS',
                                        'OT SOC Initial Evaluation - Long Term EI',
                                        'ST Initial Evaluation - Acute',
                                        'ST Initial Evaluation - Acute EI',
                                        'ST Initial Evaluation - Long Term',
                                        'ST Initial Evaluation - Long Term EI',
                                        'ST SOC Initial Evaluation - Acute',
                                        'ST SOC Initial Evaluation - Acute - OASIS',
                                        'ST SOC Initial Evaluation - Acute EI',
                                        'ST SOC Initial Evaluation - Long Term',
                                        'ST SOC Initial Evaluation - Long Term - OASIS',
                                        'ST SOC Initial Evaluation - Long Term EI'
                                    )
                                ) AND
                                mssql_schedulemaster.ScheduleDate >= date(admission_visit_date) limit 1),
                            0) = 1
                    ");

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
