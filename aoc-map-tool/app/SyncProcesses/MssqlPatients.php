<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Notifications;
use Carbon\Carbon;
use App\Notes;

class MssqlPatients
{
    /**
     * Sync Patients.
     */
    public function syncPatients()
    {
        echo "Sync Patients List \n";
        $syncType = 'patients';
        $mssqlTableName = '[Excelss].[GENERAL_Patients]';
        $tableName = 'mssql_patients';
        $fieldName = 'BillingInternalPatientID';
        $currentDateTime = Carbon::now();
        $oldDateCutoff = Carbon::now()->subDays(5);
        
        //first make sure intake is done syncing for the day
        $batch_info = H::getHelper('Sync')->get_batch_information('intake');
        if ($batch_info['last_updated_id'] != 'donefortheday') {
            echo 'Skipping because intake sync is not done for the day';
            exit;
        }
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
				and IsLatestAdmit = 1
			ORDER BY [$fieldName] ASC, EpisodeStartDate ASC";
            H::ExcelLog($tsql,'patientSync.log');
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);
        
        if ($getRecords == false) {
            echo 'error';
            die();
        }
        //get date from 5 days ago
        $yesterday = Carbon::yesterday();

        $recordCount = 0;
        $trackingFields = ['PatientAccount','PatientName','Patient_Gender','Patient_Address1','Patient_Address2','Patient_City','Patient_Zip','Patient_State','Patient_Phone','Patient_MobilPhone','Patient_DOB','AuthorizedWeeklyHours','CNAAuthorizedWeeklyHours',
                'CF_RespWeeklyHrs','CF_WorkForceDevProj','PreferenceList','ClientCareCoordinator',
                'CC2','Payer','RespitePayer', 'Patient_CurrentStatusID'];

        $ignoreSyncFromKanTime = ['CF_ClinicianDiscipline','CF_DME','CF_LiftingRequired','CF_MeetingNurses','CF_MultipleClientHome','CF_NurseLanguage','Languages','PetsinHome','PetsNurseCannotOwn','CF_ServicesNeeded','CF_Skills','CF_Specialized','Biography','NTHNotes','CF_TheSale','CF_Weight','CF_Schooldistrict','CF_SchoolName','CF_SharedAgency','PreferenceList'];
		
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            H::ExcelLog($row['Patient_FirstName'] . $row['Patient_LastName'] . ' (' . $row['PatientAccount'] . ') Eps: ' . $row['EpisodeNumber'] . ' : ' . $row['EpisodeStartDate'],'patientSync.log');
            $mssqlEpisodeStart = $row['EpisodeStartDate'];
            $recordCount++;
            //H::ExcelLog('count: ' . $recordCount,'patientSync.log');
            //var_dump($row);
            echo "\n";
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
            echo $recordCount . ' - ' .  $last_updated_date . ' - ' . $last_updated_id . "\n";
            //get existing record
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            $originalData = json_decode(json_encode($dbRecord), true);

            $isNewRecord = 0;
            if (!empty($dbRecord)) {
                if(isset($dbRecord[0]->created_at) && $dbRecord[0]->created_at > $oldDateCutoff ){
                    $isNewRecord = 1;
                }
            }

            //set data to newRow array
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
                    //echo 'The value for ' . $newKey . ' ignored to sync from KanTime \n';
                    continue;
                }
                if (Schema::hasColumn($tableName, $newKey)) {
                    //if (trim($value)!='' || $isNewRecord==0) {
                        if(in_array($newKey,$trackingFields)){
                            if(isset($originalData[0][$newKey]) && $originalData[0][$newKey]!=$value){
                                echo 'The value for ' . $newKey . ' changed from "' . $originalData[0][$newKey] . '" to "' . $value . '"' . "\n";
                                H::ExcelLog('The value for ' . $newKey . ' changed from "' . $originalData[0][$newKey] . '" to "' . $value . '"', 'patientSyncMismatch.log');
                            }
                        }

                        //$newRow[$newKey] = $value;
                    //}
                }
            }
            //set mssqlPatientID to PatientID and PatientID to BillingInternalPatientID
            //$newRow['mssqlPatientID'] = $row['PatientID'];
            //$newRow['PatientID'] = $row['BillingInternalPatientID'];
            //get aoc_facility_id
            // remark off for speed sake
            /*
            $aocFacility = DB::table('mssql_facilities')->select("id")->where('FacilityID',$row['FacilityID'])->first();
            if (!empty($aocFacility)) {
                $newRow['aoc_facility_id'] = $aocFacility->id;
            }else{
                $newRow['aoc_facility_id'] = 21;
            }
            H::ExcelLog('FacilityID: ' . $row['FacilityID'] . ' - ' . 'aoc_facility_id: ' . $newRow['aoc_facility_id'],'patientSync.log');
           */
          /*
            $coordinator_error = '';
           $newRow['ccc_id'] = '';
           $newRow['ic_id'] = '';
           $newRow['ccm_id'] = '';

            //set ids of care team
            //CCC - Client Care Coordinator
            H::ExcelLog('source data','patientSync.log');
            H::ExcelLog(print_r($row,true),'patientSync.log');
            if(trim($row['ClientCareCoordinator'])){
                $teamUserId = H::getHelper('ConstantHelper')->getUserIdByProviderId(trim($row['ClientCareCoordinator']));
                if($teamUserId!='') {
                    $newRow['ccc_id'] = $teamUserId;
                }
            }
            //Intake Coordinator
            if(trim($row['CC2'])){
                $teamUserId = H::getHelper('ConstantHelper')->getUserIdByProviderId(trim($row['CC2']));
                if($teamUserId!='') {
                    $newRow['ic_id'] = $teamUserId;
                }
            }
            //Case Manager
            if(trim($row['CaseManagerID'])){
                $teamUserId = H::getHelper('ConstantHelper')->getUserIdByProviderId(trim($row['CaseManagerID']));
                if($teamUserId!='') {
                    $newRow['ccm_id'] = $teamUserId;
                }
            }
                */
            $newRow['is_updated'] = '1';
            $newRow['payer_group'] = $payer_group;
            $newRow['Payer'] = $payer;
            //$newRow['Search_Name'] = str_replace('  ', ' ', str_replace(',', '', $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' ' . $row['Patient_FirstName']));
            //$newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            //$newRow['Patient_CurrentStatusID'] = $row['Patient_CurrentStatusID'];
            //if ($row['Patient_CurrentStatusID'] == 7) {
            //    $newRow['IntakeStatus'] = 'Admitted';
            //}

            if (!empty($dbRecord)) {
                echo 'Existing Record' . "\n";
                H::ExcelLog('Existing Record Found: ' . $dbRecord[0]->id,'patientSync.log');
                //$mysqlEpisodeStart = $dbRecord[0]->EpisodeStartDate;
                //echo 'episodes: ' . $mysqlEpisodeStart . ' - ' . $mssqlEpisodeStart . "\n";

                //H::ExcelLog('episodes: ' . $mysqlEpisodeStart . ' - ' . $mssqlEpisodeStart,'patientSync.log');
                //H::ExcelLog('admits: ' . $dbRecord[0]->AdmitNo . ' - ' . $row['AdmitNo'],'patientSync.log');
                //set date_not_meeting_nurses when CF_MeetingNurses did start with Yes and now it starts wih No
                
                $originalMeetingNurses = '';
                $currentMeetingNurses = '';
                if(isset($dbRecord[0]->CF_MeetingNurses)) {
                    $originalMeetingNurses = $dbRecord[0]->CF_MeetingNurses;
                }
                if(isset($row['CF_MeetingNurses'])) {
                    $currentMeetingNurses = $row['CF_MeetingNurses'];
                }
                if(strtoupper(substr($originalMeetingNurses, 0, 3))=='YES' && strtoupper(substr($currentMeetingNurses, 0, 2))=='NO'){
                    $newRow['date_not_meeting_nurses'] = date_format(date_create(), 'Y-m-d');
                }elseif(strtoupper(substr($currentMeetingNurses, 0, 3))=='YES'){
                    $newRow['date_not_meeting_nurses'] = null;
                }

                
                $ignoreBecauseIntake = 0;
                H::ExcelLog('    Patient_CurrentStatusID in MSSQL: ' . $row['Patient_CurrentStatusID'],'patientSync.log');
                H::ExcelLog('    Patient_CurrentStatusID in mySQL: ' . $dbRecord[0]->Patient_CurrentStatusID,'patientSync.log');
                H::ExcelLog('    is_intake in mySQL: ' . $dbRecord[0]->is_intake,'patientSync.log');
                if($dbRecord[0]->is_intake == 1){
                    $ignoreBecauseIntake = 1;
                    if($row['Patient_CurrentStatusID']!=5 && $row['Patient_CurrentStatusID']!=2 && $row['Patient_CurrentStatusID']!=3) {
                        $ignoreBecauseIntake = 0;
                    }
                } 
                if ($ignoreBecauseIntake==0) {
                        var_dump($newRow);
                        echo "\n";
                        H::ExcelLog('    Updating mssql_patients where ' . $fieldName . ' = "' . $uniqueID . '"','patientSync.log');
                        H::ExcelLog(print_r($newRow,true),'patientSync.log');
                        echo 'Update Record' . "\n";
                        echo $fieldName . ' - ' . $uniqueID . "\n";
                        DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
                    
                    $aoc_patient_id = $dbRecord[0]->id;
                    //if($newRow['ccc_id']=='') {
                    //    $coordinator_error .= 'Error setting CCC';
                    //}
                    //if($newRow['ic_id']=='') {
                    //    $coordinator_error .= ' Error setting Intake Coordinator';
                   // }
                    //if($newRow['ccm_id']=='') {
                    //    $coordinator_error .= ' Error setting CCM';
                    //}
                    
                    //if($coordinator_error!=''){
                    //    if($row['Patient_CurrentStatusID']==1 || $row['Patient_CurrentStatusID']==4 || $row['Patient_CurrentStatusID']==7 
                    //        || $row['Patient_CurrentStatusID']==6 || $row['Patient_CurrentStatusID']==8){
                    //        $notificationText = 'Issue with ' . $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' (' . $row//
                    //['PatientAccount'] . ') ' . $coordinator_error; 
                    //        Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'client', //
                    //'related_item' => $aoc_patient_id]);
                    //    }
                    //}
                    
        
                    H::ExcelLog('    Looking for Active NTH record for: ' . $aoc_patient_id,'patientSync.log');
                    //see if this patient is on NTH and update map if needed
                    $nthAccount = DB::table('NeedToHire_Accounts')
                                    ->select('id','user_id')
                                    ->where('NeedToHire_Accounts.aoc_patient_id',$aoc_patient_id)
                                    ->where('NeedToHire_Accounts.IsActive',1)->first();
                    if(isset($nthAccount->id)){
                        $nthAccountId = $nthAccount->id;
                        H::ExcelLog('    Found Active NTH record: ' . $nthAccountId,'nthMapUpdates.log');
                        //see if the patient has been discharged or non-admit and disable on NTH and map
                        if($row['Patient_CurrentStatusID']==3 || $row['Patient_CurrentStatusID']==5){
                            H::ExcelLog('    Remove Active NTH record: ' . $nthAccountId,'nthMapUpdates.log');
                            $reason = '';
                            if($row['Patient_CurrentStatusID']==3) {
                                $reason = 'Non-admit';
                                $this->removeClientSchedule($aoc_patient_id);
                            }
                            if($row['Patient_CurrentStatusID']==5) {
                                $reason = 'Discharged';
                            }
                            $notificationText = '';
                            if($row['Patient_CurrentStatusID']==3){
                                $notificationText = 'NTH Account for ' . $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' (' . $row['PatientAccount'] . ') should be checked because Patient Status is Non-Admit.'; 

                                //add note to client 
                                $insert = Notes::insert(['entity' => 'client', 'entity_id' => $aoc_patient_id,'notes' => $notificationText, 'created_by' => 1, 'updated_by' => 1, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                                if(isset($nthAccount->user_id) && $nthAccount->user_id!=0 && $nthAccount->user_id!=''){
                                    //this will go to the Inake Coordinator - $newRow['ic_id']  and the recruiter - $recruiter_id
                                    Notifications::addNotification($data = ['user_id' => $nthAccount->user_id, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }else{
                                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }
                                
                                if(isset($newRow['ic_id']) && $newRow['ic_id']!=0 && $newRow['ic_id']!=''){
                                    //this will go to the CCC - $newRow['ccc_id'] and the recruiter - $recruiter_id
                                    Notifications::addNotification($data = ['user_id' => $newRow['ic_id'], 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }else{
                                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }
                                echo $notificationText . ' notification sent to admin' . "\n";
                            }
                            if($row['Patient_CurrentStatusID']==5){
                                $notificationText = 'NTH Account for ' . $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' (' . $row['PatientAccount'] . ') should be checked because Patient Status is Discharged.'; 
                                //add note to client 
                                $insert = Notes::insert(['entity' => 'client', 'entity_id' => $aoc_patient_id,'notes' => $notificationText, 'created_by' => 1, 'updated_by' => 1, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                                if(isset($nthAccount->user_id) && $nthAccount->user_id!=0 && $nthAccount->user_id!=''){
                                    Notifications::addNotification($data = ['user_id' => $nthAccount->user_id, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }else{
                                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }
                                if(isset($newRow['ccc_id']) && $newRow['ccc_id']!=0 && $newRow['ccc_id']!=''){
                                    Notifications::addNotification($data = ['user_id' => $newRow['ccc_id'], 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }else{
                                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'client', 'related_item' => $aoc_patient_id]);
                                }
                                echo $notificationText . ' notification sent to admin' . "\n";
                            }
                            H::ExcelLog($notificationText, 'nthMapUpdates.log');
                            $insert = Notes::insert(['entity' => 'NeedToHire', 'entity_id' => $nthAccountId, 'nth_id' => $nthAccountId, 
                            'notes' => $notificationText, 'created_by' => 1, 'updated_by' => 1, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                        }
                        //now update map (which will also be disabled for 3 & 5)          
                        H::ExcelLog('update NTH Map Record: ' . $nthAccountId, 'patientSync.log');              
                        H::getHelper('NTHChecklist')->AddEditNTHMapRecord($nthAccountId);
                        H::getHelper('NTHChecklist')->setNTHScheduleToMap($nthAccountId);
                        //send notification to recruiter if patient who is On Hold or Hospitalized has an appointment in the next 3 days
                        if($row['Patient_CurrentStatusID']==6 || $row['Patient_CurrentStatusID']==8){
                            if($row['Patient_CurrentStatusID']==6){
                                $status = 'On Hold';
                            }else{
                                $status = 'Hospitalized';
                            }
                            //get appointments within 3 days
                            $qry = "SELECT rt.id, rt.user_id, st.type, rt.Candidate, appointment_date, appointment_time
                                    FROM `recruiting_schedule` 
                                    left join mssql_recruitertracker rt on tracker_id = rt.id
                                    left join schedule_types st on type_id = st.id 
                                    WHERE recruiting_schedule.aoc_patient_id = " . $aoc_patient_id . " and appointment_date >= CURRENT_DATE and appointment_date <= date_add(CURRENT_DATE, INTERVAL 3 DAY)";
                            $appointments = DB::select($qry);
                            foreach($appointments as $appt){
                                $tmp = 'a ' . $appt->type;
                                if($appt->type=='IPEA') {
                                    $tmp = 'an ' . $appt->type;
                                }
                                $notificationText = 'Candidate ' . $appt->Candidate . ' has ' . $tmp . ' for client ' . $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'] . ' on ' . $appt->appointment_date . ' but the client is currently ' . $status; 
                                if(isset($appt->user_id) && $appt->user_id!=0 && $appt->user_id!='' && isset($appt->id)){
                                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'trackerDetails', 'related_item' => $appt->id]);
                                    Notifications::addNotification($data = ['user_id' => $appt->user_id, 'title' => $notificationText, 'type' => 'trackerDetails', 'related_item' => $appt->id]);
                                }
                                echo $notificationText . "\n";
                                $insert = Notes::insert(['entity' => 'NeedToHire', 'entity_id' => $nthAccountId, 'nth_id' => $nthAccountId, 'notes' => $notificationText, 'created_by' => 1, 'updated_by' => 1, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);
                            }
                        }
                    }else{
                        H::ExcelLog('    No NTH found','nthMapUpdates.log');
                    }
                        
                } else {
                    H::ExcelLog('     Skip because this inactive patient is in intake','patientSync.log');
                    echo 'skip because this inactive patient is in intake' . "\n";
                }
            } else {
                //H::ExcelLog('     Inserting','patientSync.log');
                //H::ExcelLog(print_r($newRow,true),'patientSync.log');
                //echo 'Insert Record' . "\n";
                /*
                $aoc_patient_id = DB::table($tableName)->insertGetId($newRow);

                if (isset($newRow['is_intake']) && $newRow['is_intake'] == 1) {
                    DB::table('intake')->insert([
                        'aoc_patient_id' => $aoc_patient_id,
                        'created_by' => '1',
                        'updated_by' => '1',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
                    */
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        H::ExcelLog('finished with loop','patientSync.log');
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   Set all records with Patient_CurrentStatusID = 4 and updated_at < today to non-admit
        /*
        if ($recordCount == 0) {
            H::ExcelLog('Setting to done for the day','patientSync.log');
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
            $rawData = DB::table('mssql_patients')->select('*')->where('Patient_CurrentStatusID', 4)->where('updated_at','<',date_format(date_create(), 'Y-m-d'))->get();
            $batchCount = count($rawData);
            if($batchCount>0){
                $rawData = json_decode(json_encode($rawData), true);
                foreach ($rawData as $row) {
                    H::ExcelLog($row['PatientName'] . ' - ' . $row['Patient_CurrentStatusID'] . ' - ' . $row['updated_at'],'nonAdmitTest.log');   
                }
            }
            $updateDeleted = [];
        }
            */
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }


    public function removeClientSchedule($patientId){
        /*
        $reqData = $request->all();
        $nthAccountId = DB::table('NeedToHire_Accounts')->select("*")->where("aoc_patient_id",$patientId)->where("IsActive",1)->first();
        
        if($nthAccountId) {
            DB::table("NeedToHire_Schedules")->where("aoc_nthaccounts_id",$nthAccountId->id)->where("IsActive",1)->update(['IsActive'=>0,'RemovalReason'=>'Non-admit']);
        }
            */
    }
	
	public function syncSpecialPayerGroup() {
		$conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT * FROM [Excelss].[GENERAL_SpecialPayerGroupMapping]';
        $getRecords = sqlsrv_query($conn, $tsql);
        
        if ($getRecords == false) {
            echo 'error';
            die();
        }
		DB::table('special_payer_group')->truncate();
		while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
			DB::table('special_payer_group')->insert($row);
		}
    }
}
