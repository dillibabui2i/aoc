<?php

namespace App\Helpers;

use App\MssqlRecruitertracker;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\RecruitingSchedule;


class Sync
{
    public static $setting_sessionId;

    /**
     * Functions that manage sync batching.
     */
    public function setSessionId($process_type)
    {
        $tmp = $process_type . '_' . date('Y-m-d_H-i');
        self::$setting_sessionId = $tmp;
    }

    public function getSessionId()
    {
        return self::$setting_sessionId;
    }

    protected function check_batch_status($process_type)
    {
        H::ExcelLog('check_batch_status', $process_type . '.log');

        $result = DB::table('process_settings')->select('*')->where('process', '=', $process_type)->limit(1)->get();
        $batch_status = 0;
        $batch_started = '';
        if (count($result) > 0) {
            $batch = $result[0];
            echo sprintf("Running sync process `%s` within the batch of %s: %s\n",
                $batch->process,
                $batch->batch_size,
                json_encode([
                    'LAST_ETL' => $batch->last_updated_date,
                    'LAST_ID' => $batch->last_updated_id,
                    'LAST_START' => $batch->last_batch_start
                ])
            );
            $batch_started = $result[0]->last_batch_start;
            $batch_status = $result[0]->running_now;
        }
        H::ExcelLog($batch_status . ' - ' . $batch_started, $process_type . '.log');
        //if $batch_status == 1 check to see if we should restart it
        if ($batch_status == 1) {
            $cutoffdate = date_modify(date_create(), '-60 minutes');
            $mydate = date_format($cutoffdate, 'Y-m-d H:i:s');
            if (strtotime($batch_started) < strtotime($mydate)) {
                H::ExcelLog('reset batch', $process_type . '.log');
                $batch_status = 0;
                $this->set_batch_status($batch_status, $process_type);
            }
        }

        return $batch_status;
    }

    protected function set_batch_status($status, $process_type)
    {
        $table = 'process_settings';
        if ($status == 1) {
            $Start = date_format(date_create(), 'Y-m-d H:i:s');
            $End = '';
        } else {
            $Start = '';
            $End = date_format(date_create(), 'Y-m-d H:i:s');
        }

        $result = DB::table('process_settings')->select('*')->where('process', '=', $process_type)->limit(1)->get();
        if (count($result) == 0) {
            $tmp['process'] = $process_type;
            $tmp['last_updated_date'] = '2001-01-01T12:00:00';
            $tmp['last_updated_id'] = '';
            $tmp['batch_size'] = '10';
            $tmp['last_batch_start'] = $Start;
            $tmp['last_batch_end'] = $End;
            $tmp['running_now'] = $status;
            DB::table($table)->insert($tmp);
        } else {
            if ($status == 1) {
                $tmp['last_batch_start'] = $Start;
                $tmp['last_batch_end'] = '';
                $tmp['running_now'] = 1;
                DB::table($table)->where('process', $process_type)->update($tmp);
            } else {
                $tmp['last_batch_end'] = $End;
                $tmp['running_now'] = 0;
                DB::table($table)->where('process', $process_type)->update($tmp);
            }
        }
    }

    public function update_last_updated($process_type, $last_updated_date, $last_updated_id)
    {
        $table = 'process_settings';
        $tmp['last_updated_date'] = $last_updated_date;
        $tmp['last_updated_id'] = $last_updated_id;
        DB::table($table)->where('process', $process_type)->update($tmp);
    }

    public function get_batch_information($process_type)
    {
        $lastUpdate = '2001-01-01T12:00:00';
        $lastUpdateId = '';
        $batchSize = '100';
		$comment = '';

        $result = DB::table('process_settings')->select('*')->where('process', '=', $process_type)->limit(1)->get();
        if (count($result) > 0) {
            if (isset($result[0]->last_updated_date) && $result[0]->last_updated_date != '') {
                $lastUpdate = $result[0]->last_updated_date;
            }
            if (isset($result[0]->last_updated_id) && $result[0]->last_updated_id != '') {
                $lastUpdateId = $result[0]->last_updated_id;
            }
            if (isset($result[0]->batch_size) && $result[0]->batch_size != '') {
                $batchSize = $result[0]->batch_size;
            }
			if (isset($result[0]->comment) && $result[0]->comment != '') {
                $comment = $result[0]->comment;
            }
        }

        return ['last_updated_date' => $lastUpdate, 'last_updated_id' => $lastUpdateId, 'batch_size' => $batchSize, 'comment' => $comment];
    }

    public function startBatch($process_type)
    {
        $this->setSessionId($process_type);
        $sessionId = $this->getSessionId();
        H::ExcelLog('Starting ' . $process_type . ' batch - ' . $sessionId, $process_type . '.log');
        H::ExcelLog('Check batch', $process_type . '.log');
        echo 'Starting ' . $process_type . ' batch - ' . $sessionId . "\n";
        echo 'Check batch' . "\n";
        //check to see if a batch is currently running
        $starttime = microtime(true);
        $batch_status = $this->check_batch_status($process_type);
        echo 'batch status: ' . $batch_status . "\n";
        if ($batch_status == 1) {
            H::ExcelLog('**************************************Skipping batch', $process_type . '.log');
            echo 'Skipping batch' . "\n";

            return -1;  // -1 means skipped
        }

        //mark batch as running
        $this->set_batch_status(1, $process_type);

        return 1;
    }

    public function stopBatch($process_type)
    {
        $sessionId = $this->getSessionId();
        H::ExcelLog('Stopping batch ' . $process_type . ' batch - ' . $sessionId, $process_type . '.log');
        $this->set_batch_status(0, $process_type);
    }

    public function OpenConnection()
    {
        $serverName = Crypt::decryptString(env('MSSQL_SERVER'));
        $databaseName = Crypt::decryptString(env('MSSQL_DBNAME'));
        $userName = Crypt::decryptString(env('MSSQL_USERNAME'));
        $password = Crypt::decryptString(env('MSSQL_PASSWORD'));
        $connectionOptions = [
            'Database'=>$databaseName,
            'Uid'=>$userName,
            'PWD'=>$password,
            'Encrypt'=>true,
            'ReturnDatesAsStrings'=>true,
            'TrustServerCertificate'=>true, ];
        $conn = sqlsrv_connect($serverName, $connectionOptions);
        if ($conn == false) {
            echo 'errors';
            die(print_r(sqlsrv_errors(), true));
        }

        return $conn;
    }

    public function UpdateTrackerToMssql($trackerId = '',$mode = '', $aoc_patient_id = '', $duplicate = 0)
    {
        if (env('MSSQL_DBNAME') == 'staging') {
            return 'skip';
        }

        H::ExcelLog('in updateTrackerToMssql: ' . $trackerId, 'recruiterTrackerUpdate.log');
        if ($trackerId == '') {
            return 'No Tracker';
        }
        if ($mode!='mssql'){
            return 'Error';
        }

        //get array of schedule types
        $typeList = DB::table('schedule_types')->get();
        $typeData = [];
        if (count($typeList)) {
            foreach($typeList as $item){
                $typeData[$item->type] = $item->id;
            }
        }
        H::ExcelLog(print_r($typeData,true),'recruiterTrackerUpdate2.log');
        $result = 'success';
        $rt = MssqlRecruitertracker::where('id', $trackerId)->first();
        H::ExcelLog(print_r($rt, true), 'recruiterTrackerUpdate2.log');

        $conn = H::getHelper('Sync')->OpenConnection();
        if(isset($rt)){
            if ($trackerId!=9999999)
            {
                H::ExcelLog('new way!!!!!', 'recruiterTrackerUpdate.log');
                //first find out if we need to update or insert
                $myId = $rt->id;
                if($rt->mssqlID!='' && $rt->mssqlID!=0) {
                    $myId = $rt->mssqlID;
                }
                $tsql = 'SELECT top 1 * FROM Excelss.RecruiterTracker WHERE [ID]  = ' . $myId;
                $originalSql = sqlsrv_query($conn, $tsql);
                $originalIPEA = '';
                $originalPEPI = '';
                $originalWEPI = '';
                $originalCM = '';
                $originalCS = '';
                while ($row = sqlsrv_fetch_array($originalSql, SQLSRV_FETCH_ASSOC)) {
                    $originalIPEA = $row['IPEA_Scheduled'];
                    $originalPEPI = $row['PEPI_Scheduled'];
                    $originalWEPI = $row['WEPI_Scheduled'];
                    $originalCS = $row['Candidate_Start_Date'];
                    $originalCM = $row['Client_Match_Date'];
                }

                if($duplicate==1){
                    $myId = '100' . $myId;
                    H::ExcelLog('updating a duplicate record to mssql - ' . $myId,'recruiterTrackerUpdate.log');
                }
                $tsql = 'SELECT top 1 [ID], Candidate FROM Excelss.RecruiterTracker WHERE [ID]  = ' . $myId;
                echo $tsql . "\n";
                $getRecords = sqlsrv_query($conn, $tsql);
                $mode = 'Add';
                while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
                    var_dump($row);
                    echo "\n";
                    $mode = 'Edit';
                }
                sqlsrv_free_stmt($getRecords);
                echo '******   Mode:  ' . $mode . '   *******' . "\n";

                $data = [];
                if ($mode == 'Edit') {
                    $data['ID'] = $myId;
                    if($rt->mssqlID!='' && $rt->mssqlID!=0) {
                        $data['ID'] = $rt->mssqlID;
                    }
                    $data['Created'] = H::getMssqlDate($rt->Created);
                    $data['Modified'] = H::getMssqlDate($rt->updated_at);
                    $data['CreatedBy'] = $rt->Recruiter;
                    $data['ModifiedBy'] = $rt->Recruiter;
                } else {
                    $data['ID'] = $myId;
                    $data['Created'] = H::getMssqlDate($rt->created_at);
                    $data['Modified'] = H::getMssqlDate($rt->updated_at);
                    $data['CreatedBy'] = $rt->Recruiter;
                    $data['ModifiedBy'] = $rt->Recruiter;
                }
                $data['Candidate'] = $rt->Candidate;
                $data['Date_Contacted'] = H::getMssqlDate($rt->Date_Contacted);
                $data['Clinician_ID'] = $rt->Clinician_ID;
                $data['Bilingual'] = $rt->Bilingual;
                $data['Skillset'] = $rt->Skillset;
                $data['Candidate_Current_Status'] = $rt->Candidate_Current_Status;
                $data['Total_Hours'] = $rt->Total_Hours;
                $data['Agreed_Pay_Rate'] = $rt->Agreed_Pay_Rate;
                $data['GPPCT'] = $rt->GP;
                $data['Approved_By'] = $rt->Approved_By;
                $data['Candidate_Notes'] = $rt->Candidate_Notes;
                $data['Bonus_Status'] = $rt->Bonus_Status;
                $data['Bonus_Paid_Date'] = H::getMssqlDate($rt->Bonus_Paid_Date);
                $data['Schedule'] = $rt->Schedule;

                $data['Recruiter'] = $rt->Recruiter;
                $data['UserID'] = $rt->UserID;
                $ProviderID = '';
                $dbRecord = DB::table('mssql_providers')->select('ProviderID')->where('id','=',$rt->aoc_provider_id)->first();
                if (!empty($dbRecord)) {
                    $ProviderID = $dbRecord->ProviderID;
                }
                $data['ProviderID'] = $ProviderID;
                $PatientID = '';
                $data['PatientID'] = '';
                $data['Client_Name'] = $rt->Client_Name;
                $data['Client_ID'] = $rt->Client_ID;
                $data['Branch_Location'] = $rt->Branch_Location;
                $data['Payor_Source'] = $rt->Payor_Source;
                $data['FacilityID'] = $rt->FacilityID;
                $aoc_facility_id = $rt->aoc_facility_id;
                $dbRecord = DB::table('mssql_patients')->select('*')->where('id','=',$aoc_patient_id)->first();
                if (!empty($dbRecord)){
                    var_dump($dbRecord);
                    echo "\n";
                    $PatientID = $dbRecord->PatientID;
                    $data['PatientID'] = $dbRecord->PatientID;
                    $data['Client_Name'] = $dbRecord->PatientName;
                    $data['Client_ID'] = $dbRecord->PatientAccount;
                    $data['Payor_Source'] = $dbRecord->Payer;
                    if(!is_null($dbRecord->aoc_facility_id)) {
                        $aoc_facility_id = $dbRecord->aoc_facility_id;
                    }
                }
                $FacilityID = '';
                $dbRecord = DB::table('mssql_facilities')->select('*')->where('id','=',$aoc_facility_id)->first();
                if (!empty($dbRecord)){
                    $FacilityID = $dbRecord->FacilityID;
                    $data['Branch_Location'] = $dbRecord->FacilityName;
                    $data['FacilityID'] = $FacilityID;
                }

                $data['IPEA_Outcome'] = $rt->IPEA_Outcome;
                $data['IPEATime'] = $rt->IPEA_Time;
                $data['First_Shift_Not_Worked'] = $rt->First_Shift_Not_Worked;
                $data['Training_Shift'] = H::getMssqlDate($rt->Training_Shift);
                $data['School_Set_Up_Due_Date'] = H::getMssqlDate($rt->School_Set_Up_Due_Date);


                $data['IPEA_Scheduled'] = H::getMssqlDate($rt->IPEA_Scheduled);
                $data['IPEA_Date'] = H::getMssqlDate($rt->IPEA_Date);
                //get ipea date and ipea scheduled from schedules
                $myDate = '';
                $mySched = '';
                $related = RecruitingSchedule::where(['type_id' => $typeData['IPEA'], 'tracker_id' => $trackerId, 'aoc_patient_id' => $aoc_patient_id])->orderBy('id','desc')->first();
                if ($related){
                    $data['IPEA_Date'] = $related->appointment_date;
                    $data['IPEA_Scheduled'] = $related->scheduled_date;
                    $data['IPEATime'] = $related->appointment_time;
                }
                H::ExcelLog('IPEA: ' . $data['IPEA_Scheduled'],'recruiterTrackerUpdate.log');
                $data['PEPI_Scheduled'] = H::getMssqlDate($rt->PEPI_Scheduled);
                $data['PEPI_Date'] = H::getMssqlDate($rt->PEPI_Date);
                $data['PEPI_Time'] = $rt->PEPI_Time;
                $data['PEPIOutcome'] = $rt->{'PEPI-Outcome'};
                //get pepi date and pepi scheduled from schedules
                $myDate = '';
                $mySched = '';
                $related = RecruitingSchedule::where(['type_id' => $typeData['PEPI'], 'tracker_id' => $trackerId, 'aoc_patient_id' => $aoc_patient_id])->orderBy('id','desc')->first();
                if ($related){
                    $data['PEPI_Date'] = $related->appointment_date;
                    $data['PEPI_Scheduled'] = $related->scheduled_date;
                    $data['PEPI_Time'] = $related->appointment_time;
                }
                H::ExcelLog('PEPI: ' . $data['PEPI_Scheduled'],'recruiterTrackerUpdate.log');

                $data['WEPI_Scheduled'] = H::getMssqlDate($rt->WEPI_Scheduled);
                $data['WEPI_Date'] = H::getMssqlDate($rt->WEPI_Date);
                $data['WEPI_Time'] = $rt->WEPI_Time;
                $data['WEPIOutcome'] = $rt->{'WEPI-Outcome'};
                //get wepi date and wepi scheduled from schedules
                $myDate = '';
                $mySched = '';
                $related = RecruitingSchedule::where(['type_id' => $typeData['WEPI'], 'tracker_id' => $trackerId, 'aoc_patient_id' => $aoc_patient_id])->orderBy('id','desc')->first();
                if ($related){
                    $data['WEPI_Date'] = $related->appointment_date;
                    $data['WEPI_Scheduled'] = $related->scheduled_date;
                    $data['WEPI_Time'] = $related->appointment_time;
                }
                H::ExcelLog('WEPI: ' . $data['WEPI_Scheduled'],'recruiterTrackerUpdate.log');

                //get Clinical_Competency_Review and ccr_scheduled dates from schedules
                $ccr_sched = '';
                $ccr_date = '';
                $relatedCCR = RecruitingSchedule::where(['type_id' => $typeData['CCR'], 'tracker_id' => $trackerId, 'aoc_patient_id' => $aoc_patient_id])->orderBy('id','desc')->first();
                H::ExcelLog('CCR','recruiterTrackerUpdate.log');
                if ($relatedCCR){
                    $ccr_date = $relatedCCR->appointment_date;
                    $ccr_sched = $relatedCCR->scheduled_date;
                }
                $data['Clinical_Competency_Review'] = H::getMssqlDate($ccr_date);
                $data['ccr_scheduled'] = H::getMssqlDate($ccr_sched);


                $data['Candidate_Start_Date'] = H::getMssqlDate($rt->Candidate_Start_Date);
                //get Client Match Date date from schedules
                $data['Client_Match_Date'] = H::getMssqlDate($rt->Client_Match_Date);
                $relatedCCR = RecruitingSchedule::where(['type_id' => $typeData['Candidate Start'], 'tracker_id' => $trackerId, 'aoc_patient_id' => $aoc_patient_id])->orderBy('id','desc')->first();
                H::ExcelLog('Candidate Start','recruiterTrackerUpdate.log');
                if ($relatedCCR){
                    $cm_date = $relatedCCR->scheduled_date;
                    $data['Client_Match_Date'] = H::getMssqlDate($cm_date);
                    $data['Candidate_Start_Date'] = H::getMssqlDate($relatedCCR->appointment_date);
                }
                H::ExcelLog('CM: ' . $data['Client_Match_Date'],'recruiterTrackerUpdate.log');
                H::ExcelLog('CS: ' . $data['Candidate_Start_Date'],'recruiterTrackerUpdate.log');


                //delete any scheduled date if duplicate and it's the same
                if($duplicate==1){
                    if($rt->IPEA_Scheduled == $originalIPEA) {
                        $data['IPEA_Scheduled'] = '';
                    }
                    if($rt->PEPI_Scheduled == $originalPEPI) {
                        $data['PEPI_Scheduled'] = '';
                    }
                    if($rt->WEPI_Scheduled == $originalWEPI) {
                        $data['WEPI_Scheduled'] = '';
                    }
                    if($rt->Candidate_Start_Date == $originalCS) {
                        $data['Candidate_Start_Date'] = '';
                    }
                    if($rt->Client_Match_Date == $originalCM) {
                        $data['Client_Match_Date'] = '';
                    }
                    H::ExcelLog('IPEA: ' . $data['IPEA_Scheduled'],'recruiterTrackerUpdate.log');
                    H::ExcelLog('PEPI: ' . $data['PEPI_Scheduled'],'recruiterTrackerUpdate.log');
                    H::ExcelLog('WEPI: ' . $data['WEPI_Scheduled'],'recruiterTrackerUpdate.log');
                    H::ExcelLog('CM: ' . $data['Client_Match_Date'],'recruiterTrackerUpdate.log');
                    H::ExcelLog('CS: ' . $data['Candidate_Start_Date'],'recruiterTrackerUpdate.log');

                }
            }else{
                H::ExcelLog('old way!!!!!', 'recruiterTrackerUpdate.log');
                //first find out if we need to update or insert
                $myId = $rt->id;
                if($rt->mssqlID!='' && $rt->mssqlID!=0) {
                    $myId = $rt->mssqlID;
                }
                $tsql = 'SELECT top 1 [ID], Candidate FROM Excelss.RecruiterTracker WHERE [ID]  = ' . $myId;
                echo $tsql . "\n";
                $getRecords = sqlsrv_query($conn, $tsql);
                $mode = 'Add';
                while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
                    var_dump($row);
                    echo "\n";
                    $mode = 'Edit';
                }
                sqlsrv_free_stmt($getRecords);
                echo '******   Mode:  ' . $mode . '   *******' . "\n";

                $data = [];
                if ($mode == 'Edit') {
                    $data['ID'] = $rt->id;
                    if($rt->mssqlID!='' && $rt->mssqlID!=0) {
                        $data['ID'] = $rt->mssqlID;
                    }
                    $data['Created'] = H::getMssqlDate($rt->Created);
                    $data['Modified'] = H::getMssqlDate($rt->updated_at);
                    $data['CreatedBy'] = $rt->Recruiter;
                    $data['ModifiedBy'] = $rt->Recruiter;
                } else {
                    $data['ID'] = $rt->id;
                    $data['Created'] = H::getMssqlDate($rt->created_at);
                    $data['Modified'] = H::getMssqlDate($rt->updated_at);
                    $data['CreatedBy'] = $rt->Recruiter;
                    $data['ModifiedBy'] = $rt->Recruiter;
                }
                $data['Candidate'] = $rt->Candidate;
                $data['Date_Contacted'] = H::getMssqlDate($rt->Date_Contacted);
                $data['Clinician_ID'] = $rt->Clinician_ID;
                $data['Bilingual'] = $rt->Bilingual;
                $data['Skillset'] = $rt->Skillset;
                $data['Candidate_Current_Status'] = $rt->Candidate_Current_Status;
                $data['IPEA_Scheduled'] = H::getMssqlDate($rt->IPEA_Scheduled);
                $data['IPEA_Date'] = H::getMssqlDate($rt->IPEA_Date);
                $data['PEPI_Scheduled'] = H::getMssqlDate($rt->PEPI_Scheduled);
                $data['PEPI_Date'] = H::getMssqlDate($rt->PEPI_Date);
                $data['PEPI_Time'] = $rt->PEPI_Time;
                $data['PEPIOutcome'] = $rt->{'PEPI-Outcome'};
                $data['WEPI_Scheduled'] = H::getMssqlDate($rt->WEPI_Scheduled);
                $data['WEPI_Date'] = H::getMssqlDate($rt->WEPI_Date);
                $data['WEPI_Time'] = $rt->WEPI_Time;
                $data['WEPIOutcome'] = $rt->{'WEPI-Outcome'};
                $data['Total_Hours'] = $rt->Total_Hours;

                $data['Candidate_Start_Date'] = H::getMssqlDate($rt->Candidate_Start_Date);

                $data['Agreed_Pay_Rate'] = $rt->Agreed_Pay_Rate;
                $data['GPPCT'] = $rt->GP;
                $data['Approved_By'] = $rt->Approved_By;
                $data['Candidate_Notes'] = $rt->Candidate_Notes;
                $data['Bonus_Status'] = $rt->Bonus_Status;
                $data['Bonus_Paid_Date'] = H::getMssqlDate($rt->Bonus_Paid_Date);
                $data['Recruiter'] = $rt->Recruiter;
                $data['UserID'] = $rt->UserID;
                $ProviderID = '';
                $dbRecord = DB::table('mssql_providers')->select('ProviderID')->where('id','=',$rt->aoc_provider_id)->first();
                if (!empty($dbRecord)) {
                    $ProviderID = $dbRecord->ProviderID;
                }
                $data['ProviderID'] = $ProviderID;
                $PatientID = '';
                $data['PatientID'] = '';
                $data['Client_Name'] = '';
                $data['Client_ID'] = '';
                $data['Branch_Location'] = '';
                $data['Payor_Source'] = '';
                $data['FacilityID'] = '';
                $dbRecord = DB::table('mssql_patients')->select('*')->where('id','=',$rt->aoc_patient_id)->first();
                if (!empty($dbRecord)){
                    var_dump($dbRecord);
                    echo "\n";
                    $PatientID = $dbRecord->PatientID;
                    $data['PatientID'] = $dbRecord->PatientID;
                    $data['Client_Name'] = $dbRecord->PatientName;
                    $data['Client_ID'] = $dbRecord->PatientAccount;
                    $data['Payor_Source'] = $dbRecord->Payer;
                }
                $FacilityID = '';
                $dbRecord = DB::table('mssql_facilities')->select('*')->where('id','=',$rt->aoc_facility_id)->first();
                if (!empty($dbRecord)){
                    $FacilityID = $dbRecord->FacilityID;
                    $data['Branch_Location'] = $dbRecord->FacilityName;
                    $data['FacilityID'] = $FacilityID;
                }
                //get ccr date from schedules
                $ccr_sched = '';
                $ccr_date = '';
                $relatedCCR = RecruitingSchedule::where(['type_id' => $typeData['CCR'], 'tracker_id' => $trackerId])->first();
                H::ExcelLog('CCR','recruiterTrackerUpdate.log');
                if ($relatedCCR){
                    $ccr_date = $relatedCCR->appointment_date;
                    $ccr_sched = $relatedCCR->scheduled_date;
                }
                //get Client Match Date date from schedules
                $data['Client_Match_Date'] = H::getMssqlDate($rt->Client_Match_Date);
                $relatedCCR = RecruitingSchedule::where(['type_id' => $typeData['Candidate Start'], 'tracker_id' => $trackerId])->first();
                H::ExcelLog('Candidate Start','recruiterTrackerUpdate.log');
                if ($relatedCCR){
                    $cm_date = $relatedCCR->scheduled_date;
                    $data['Client_Match_Date'] = H::getMssqlDate($cm_date);
                }
                $data['Clinical_Competency_Review'] = H::getMssqlDate($ccr_date);
                $data['ccr_scheduled'] = H::getMssqlDate($ccr_sched);
                $data['Training_Shift'] = H::getMssqlDate($rt->Training_Shift);
                $data['First_Shift_Not_Worked'] = $rt->First_Shift_Not_Worked;
                $data['School_Set_Up_Due_Date'] = H::getMssqlDate($rt->School_Set_Up_Due_Date);
                $data['IPEA_Outcome'] = $rt->IPEA_Outcome;
                $data['Schedule'] = $rt->Schedule;
                $data['IPEATime'] = $rt->IPEA_Time;
            }

            H::ExcelLog('Tracker Record - ' . $mode , 'recruiterTrackerUpdate.log');
            H::ExcelLog(print_r($data, true), 'recruiterTrackerUpdate.log');

            $procedure_params = [
                [&$data['ID'], SQLSRV_PARAM_IN],
                [&$data['Candidate'], SQLSRV_PARAM_IN],
                [&$data['Date_Contacted'], SQLSRV_PARAM_IN],
                [&$data['Clinician_ID'], SQLSRV_PARAM_IN],
                [&$data['Bilingual'], SQLSRV_PARAM_IN],
                [&$data['Skillset'], SQLSRV_PARAM_IN],
                [&$data['Candidate_Current_Status'], SQLSRV_PARAM_IN],
                [&$data['IPEA_Scheduled'], SQLSRV_PARAM_IN],
                [&$data['IPEA_Date'], SQLSRV_PARAM_IN],
                [&$data['PEPI_Scheduled'], SQLSRV_PARAM_IN],
                [&$data['PEPI_Date'], SQLSRV_PARAM_IN],
                [&$data['PEPI_Time'], SQLSRV_PARAM_IN],
                [&$data['PEPIOutcome'], SQLSRV_PARAM_IN],
                [&$data['WEPI_Scheduled'], SQLSRV_PARAM_IN],
                [&$data['WEPI_Date'], SQLSRV_PARAM_IN],
                [&$data['WEPI_Time'], SQLSRV_PARAM_IN],
                [&$data['WEPIOutcome'], SQLSRV_PARAM_IN],
                [&$data['Total_Hours'], SQLSRV_PARAM_IN],
                [&$data['Client_Match_Date'], SQLSRV_PARAM_IN],
                [&$data['Candidate_Start_Date'], SQLSRV_PARAM_IN],
                [&$data['Client_Name'], SQLSRV_PARAM_IN],
                [&$data['Client_ID'], SQLSRV_PARAM_IN],
                [&$data['Branch_Location'], SQLSRV_PARAM_IN],
                [&$data['Payor_Source'], SQLSRV_PARAM_IN],
                [&$data['Agreed_Pay_Rate'], SQLSRV_PARAM_IN],
                [&$data['GPPCT'], SQLSRV_PARAM_IN],
                [&$data['Approved_By'], SQLSRV_PARAM_IN],
                [&$data['Candidate_Notes'], SQLSRV_PARAM_IN],
                [&$data['Bonus_Status'], SQLSRV_PARAM_IN],
                [&$data['Bonus_Paid_Date'], SQLSRV_PARAM_IN],
                [&$data['Recruiter'], SQLSRV_PARAM_IN],
                [&$data['Modified'], SQLSRV_PARAM_IN],
                [&$data['Created'], SQLSRV_PARAM_IN],
                [&$data['CreatedBy'], SQLSRV_PARAM_IN],
                [&$data['ModifiedBy'], SQLSRV_PARAM_IN],
                [&$data['UserID'], SQLSRV_PARAM_IN],
                [&$data['ProviderID'], SQLSRV_PARAM_IN],
                [&$data['PatientID'], SQLSRV_PARAM_IN],
                [&$data['FacilityID'], SQLSRV_PARAM_IN],
                [&$data['Clinical_Competency_Review'], SQLSRV_PARAM_IN],
                [&$data['ccr_scheduled'], SQLSRV_PARAM_IN],
                [&$data['Training_Shift'], SQLSRV_PARAM_IN],
                [&$data['First_Shift_Not_Worked'], SQLSRV_PARAM_IN],
                [&$data['School_Set_Up_Due_Date'], SQLSRV_PARAM_IN],
                [&$data['IPEA_Outcome'], SQLSRV_PARAM_IN],
                [&$data['Schedule'], SQLSRV_PARAM_IN],
                [&$data['IPEATime'], SQLSRV_PARAM_IN],
                ];
            if ($mode == 'Edit') {
                H::ExcelLog('Execute [Excelss].[RecruiterTrackerUpdate] for ' . $trackerId, 'recruiterTrackerUpdate.log');
                $sql = 'EXEC [Excelss].[RecruiterTrackerUpdate] @ID = ?, @Candidate = ?, @Date_Contacted = ?, @Clinician_ID = ?, @Bilingual = ?, @Skillset = ?,
                            @Candidate_Current_Status = ?, @IPEA_Scheduled = ?, @IPEA_Date = ?, @PEPI_Scheduled = ?, @PEPI_Date = ?,
                            @PEPI_Time = ?, @PEPIOutcome = ?, @WEPI_Scheduled = ?, @WEPI_Date = ?, @WEPI_Time = ?,
                            @WEPIOutcome = ?, @Total_Hours = ?, @Client_Match_Date = ?, @Candidate_Start_Date = ?,
                            @Client_Name = ?, @Client_ID = ?, @Branch_Location = ?, @Payor_Source = ?, @Agreed_Pay_Rate = ?,
                            @GPPCT = ?, @Approved_By = ?, @Candidate_Notes = ?, @Bonus_Status = ?, @Bonus_Paid_Date = ?,
                            @Recruiter = ?, @Modified = ?, @Created = ?, @CreatedBy = ?, @ModifiedBy = ?, @UserID = ?,
                            @ProviderID = ?, @PatientID = ?, @FacilityID = ?, @Clinical_Competency_Review = ?, @ccr_scheduled = ?, @Training_Shift = ?,
                            @First_Shift_Not_Worked = ?, @School_Set_Up_Due_Date = ?, @IPEA_Outcome = ?, @Schedule = ?, @IPEATime = ?';
            } else {
                H::ExcelLog('Execute [Excelss].[RecruiterTrackerInsert] for ' . $trackerId, 'recruiterTrackerUpdate.log');
                $sql = 'EXEC [Excelss].[RecruiterTrackerInsert] @ID = ?, @Candidate = ?, @Date_Contacted = ?, @Clinician_ID = ?, @Bilingual = ?, @Skillset = ?,
                            @Candidate_Current_Status = ?, @IPEA_Scheduled = ?, @IPEA_Date = ?, @PEPI_Scheduled = ?, @PEPI_Date = ?,
                            @PEPI_Time = ?, @PEPIOutcome = ?, @WEPI_Scheduled = ?, @WEPI_Date = ?, @WEPI_Time = ?,
                            @WEPIOutcome = ?, @Total_Hours = ?, @Client_Match_Date = ?, @Candidate_Start_Date = ?,
                            @Client_Name = ?, @Client_ID = ?, @Branch_Location = ?, @Payor_Source = ?, @Agreed_Pay_Rate = ?,
                            @GPPCT = ?, @Approved_By = ?, @Candidate_Notes = ?, @Bonus_Status = ?, @Bonus_Paid_Date = ?,
                            @Recruiter = ?, @Modified = ?, @Created = ?, @CreatedBy = ?, @ModifiedBy = ?, @UserID = ?,
                            @ProviderID = ?, @PatientID = ?, @FacilityID = ?, @Clinical_Competency_Review = ?, @ccr_scheduled = ?, @Training_Shift = ?,
                            @First_Shift_Not_Worked = ?, @School_Set_Up_Due_Date = ?, @IPEA_Outcome = ?, @Schedule = ?, @IPEATime = ?';
            }
            $stmt = sqlsrv_prepare($conn, $sql, $procedure_params);
            if (!$stmt) {
                H::ExcelLog('Error In MSSQL Update', 'recruiterTrackerUpdate.log');
                H::ExcelLog(print_r(sqlsrv_errors(), true), 'recruiterTrackerUpdate.log');
                $result = 'error';
            } else {
                if (sqlsrv_execute($stmt)) {
                    while ($res = sqlsrv_next_result($stmt)) {
                        H::ExcelLog('results from MSSQL', 'recruiterTrackerUpdate.log');
                        H::ExcelLog(print_r($res, true), 'recruiterTrackerUpdate.log');
                    }
                    $result = 'success';
                } else {
                    H::ExcelLog('Error In MSSQL Update', 'recruiterTrackerUpdate.log');
                    H::ExcelLog(print_r(sqlsrv_errors(), true), 'recruiterTrackerUpdate.log');
                    $result = 'error';
                }
            }
            sqlsrv_close($conn);

            return $result;
        }else{
            return '';
        }
    }

    public function updateClientPaymentSources($clientId = '')
    {
        $clientIdQueryConstant = " AND mssql_patients.PatientID = ";
        $logFile = 'updateClientPaymentSources.log';

        // update active payers for each heirarchy to clients
        $clientPayerTemplate = "update mssql_patients
            set [heirfield] =
                (SELECT [cpspayerfield] FROM `client_payment_source` cps
                    JOIN payment_source p ON p.payersource_id =
                    CASE WHEN IFNULL(cps.additional_payer_id,0) = 0
                    THEN cps.primary_payer_id ELSE
                    cps.additional_payer_id END
                    where cps.client_id = mssql_patients.BillingInternalPatientID
                    [isprimary] and cps.payertype = [payertype]
                    and (start_date <= CURRENT_DATE or start_date = '0000-00-00 00:00:00' or start_date is null)
                    and (end_date >= CURRENT_DATE or end_date = '0000-00-00 00:00:00' or end_date is null)
                    AND p.name [isrespite] ORDER BY cps.PayerType DESC,cps.start_date DESC limit 1)
            where Patient_CurrentStatusID <> 4";
        $intakePayerTemplate = "update mssql_patients
            set [heirfield] =
                    (SELECT [cpspayerfield] FROM `client_payment_source` cps
                        JOIN payment_source p ON p.payersource_id =
                        CASE WHEN IFNULL(cps.additional_payer_id,0) = 0
                        THEN cps.primary_payer_id ELSE
                        cps.additional_payer_id END
                        where cps.intake_id = mssql_patients.IntakeID
                        [isprimary] and cps.payertype = [payertype]
                        and (start_date <= CURRENT_DATE or start_date = '0000-00-00 00:00:00' or start_date is null)
                        and (end_date >= CURRENT_DATE or end_date = '0000-00-00 00:00:00' or end_date is null)
                        AND p.name [isrespite] ORDER BY cps.PayerType DESC,cps.start_date DESC limit 1)
            where Patient_CurrentStatusID = 4";

        //kantime_primary_payer_id
        $heirField = 'kantime_primary_payer_id';
        $isRespite = "NOT LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.primary_payer_id';
        $isPrimary = 'and cps.is_primary = 1';
        $payerType = 1;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'kantime_primary_payer_id';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake kantime_primary_payer_id';
        $this->executeQuery($query, $description, $logFile);

        //secondary_payer_id
        $heirField = 'secondary_payer_id';
        $isRespite = "NOT LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.additional_payer_id';
        $isPrimary = '';
        $payerType = 2;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'secondary_payer_id';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake secondary_payer_id';
        $this->executeQuery($query, $description, $logFile);


        //tertiary_payer_id
        $heirField = 'tertiary_payer_id';
        $isRespite = "NOT LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.additional_payer_id';
        $isPrimary = '';
        $payerType = 3;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'tertiary_payer_id';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake tertiary_payer_id';
        $this->executeQuery($query, $description, $logFile);

        //respite_primary_payer
        $heirField = 'respite_primary_payer';
        $isRespite = "LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.primary_payer_id';
        $isPrimary = 'and cps.is_primary = 1';
        $payerType = 1;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'respite_primary_payer';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake respite_primary_payer';
        $this->executeQuery($query, $description, $logFile);

        //respite_secondary_payer
        $heirField = 'respite_secondary_payer';
        $isRespite = "LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.additional_payer_id';
        $isPrimary = '';
        $payerType = 2;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'respite_secondary_payer';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake respite_secondary_payer';
        $this->executeQuery($query, $description, $logFile);

        //respite_tertiary_payer
        $heirField = 'respite_tertiary_payer';
        $isRespite = "LIKE '%RESPITE%'";
        $cpsPayerField = 'cps.additional_payer_id';
        $isPrimary = '';
        $payerType = 3;
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$clientPayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'respite_tertiary_payer';
        $this->executeQuery($query, $description, $logFile);
        //intake
        $query = str_replace('[heirfield]',$heirField,
                str_replace('[cpspayerfield]',$cpsPayerField,
                str_replace('[isprimary]',$isPrimary,
                str_replace('[payertype]',$payerType,
                str_replace('[isrespite]',$isRespite,$intakePayerTemplate)))));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'intake respite_tertiary_payer';
        $this->executeQuery($query, $description, $logFile);

        //============================
        ///Added on March 10, 2024
        //==================================
        $todayDate = date('Y-m-d');
        $activeTempQueryTemplate = "update mssql_patients
            set mssql_patients.[tmpfield] =
                (select id from authorizations
                    where authorizations.PatientID = mssql_patients.PatientID and payer_id = mssql_patients.[heirfield]
                and authorizations.start_date <= '".$todayDate."'
                and authorizations.end_date >= '".$todayDate."'
                and is_invalid = 0
                and is_respite = [isrespite]
                order by start_date asc limit 1)
            WHERE 1=1";
        $futureTempQueryTemplate = "update mssql_patients set mssql_patients.[tmpfield] =
                (select id from authorizations
                where authorizations.PatientID = mssql_patients.PatientID and payer_id = mssql_patients.[heirfield]
                and authorizations.start_date > '".$todayDate."'
                and is_invalid = 0
                and is_respite = [isrespite]
                order by start_date asc limit 1)
            WHERE 1=1";
        $updateAuthTemplate = "update mssql_patients
                set mssql_patients.[typefield]  =
                coalesce(tmp_tertiary_auth, tmp_secondary_auth, tmp_primary_auth)
                WHERE 1=1";


        //set invalid auths to auth table
        $query = "update `authorizations` set is_invalid = 0 WHERE 1=1";
        if($clientId != '') {
            $query = $query ." AND PatientID = ".$clientId;
        }
        $description = 'authorizations.is_invalid to 0';
        $this->executeQuery($query, $description, $logFile);
        $query = "update `authorizations`
                    set is_invalid = 1
                    where number like '%decline%'";
        if($clientId != '') {
            $query = $query ." AND PatientID = ".$clientId;
        }
        $description = 'authorizations.is_invalid';
        $this->executeQuery($query, $description, $logFile);

        //set is_respite to auth table
        $query = "update `authorizations` set is_respite = 0 WHERE 1=1";
        if($clientId != '') {
            $query = $query ." AND PatientID = ".$clientId;
        }
        $description = 'authorizations.is_respite to 0';
        $this->executeQuery($query, $description, $logFile);
        $query = "update `authorizations`
                    left join payment_source ps on authorizations.payer_id = ps.payersource_id
                    set is_respite = 1
                    where ps.name like '%RESPITE%'";
        if($clientId != '') {
            $query = $query ." AND PatientID = ".$clientId;
        }
        $description = 'authorizations.is_respite';
        $this->executeQuery($query, $description, $logFile);

        //tmp_primary_auth
        $tmpPayerField = 'tmp_primary_auth';
        $heirField = 'kantime_primary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active tmp_primary_auth';
        $this->executeQuery($query, $description, $logFile);

        //tmp_secondary_auth
        $tmpPayerField = 'tmp_secondary_auth';
        $heirField = 'secondary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active tmp_secondary_auth';
        $this->executeQuery($query, $description, $logFile);

        //tmp_tertiary_auth
        $tmpPayerField = 'tmp_tertiary_auth';
        $heirField = 'tertiary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active tmp_tertiary_auth';
        $this->executeQuery($query, $description, $logFile);

        //active_auth_id
        $typeField = 'active_auth_id';
        $query = str_replace('[typefield]',$typeField,$updateAuthTemplate);
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active_auth_id';
        $this->executeQuery($query, $description, $logFile);


        //future tmp_primary_auth
        $tmpPayerField = 'tmp_primary_auth';
        $heirField = 'kantime_primary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future tmp_primary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future tmp_secondary_auth
        $tmpPayerField = 'tmp_secondary_auth';
        $heirField = 'secondary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future tmp_secondary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future tmp_tertiary_auth
        $tmpPayerField = 'tmp_tertiary_auth';
        $heirField = 'tertiary_payer_id';
        $isRespite = '0';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future tmp_tertiary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future_auth_id
        $typeField = 'future_auth_id';
        $query = str_replace('[typefield]',$typeField,$updateAuthTemplate);
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future_auth_id';
        $this->executeQuery($query, $description, $logFile);

        //====================update respite auth==================
        //tmp_primary_auth for respite
        $tmpPayerField = 'tmp_primary_auth';
        $heirField = 'respite_primary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }

        $description = 'active respite tmp_primary_auth';
        $this->executeQuery($query, $description, $logFile);

        //tmp_secondary_auth for respite
        $tmpPayerField = 'tmp_secondary_auth';
        $heirField = 'respite_secondary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active respite tmp_secondary_auth';
        $this->executeQuery($query, $description, $logFile);

        //tmp_tertiary_auth for respite
        $tmpPayerField = 'tmp_tertiary_auth';
        $heirField = 'respite_tertiary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$activeTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active respite tmp_tertiary_auth';
        $this->executeQuery($query, $description, $logFile);

        //active_respite_auth_id for respite
        $typeField = 'active_respite_auth_id';
        $query = str_replace('[typefield]',$typeField,$updateAuthTemplate);
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'active_respite_auth_id';
        $this->executeQuery($query, $description, $logFile);


        //future tmp_primary_auth for respite
        $tmpPayerField = 'tmp_primary_auth';
        $heirField = 'respite_primary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future respite tmp_primary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future tmp_secondary_auth for respite
        $tmpPayerField = 'tmp_secondary_auth';
        $heirField = 'respite_secondary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future respite tmp_secondary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future tmp_tertiary_auth for respite
        $tmpPayerField = 'tmp_tertiary_auth';
        $heirField = 'respite_tertiary_payer';
        $isRespite = '1';
        $query = str_replace('[tmpfield]',$tmpPayerField,str_replace('[heirfield]',$heirField,str_replace('[isrespite]',$isRespite,$futureTempQueryTemplate)));
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future respite tmp_tertiary_auth';
        $this->executeQuery($query, $description, $logFile);

        //future_respite_auth_id for respite
        $typeField = 'future_respite_auth_id';
        $query = str_replace('[typefield]',$typeField,$updateAuthTemplate);
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'future_respite_auth_id';
        $this->executeQuery($query, $description, $logFile);

        //update hh_amerigroup (clients who are eligible )
        $query = "update `mssql_patients`
                left join payment_source p1 on kantime_primary_payer_id = p1.payersource_id
                left join payment_source p2 on secondary_payer_id = p2.payersource_id
                left join payment_source p3 on tertiary_payer_id = p3.payersource_id
                set hh_eligible =
                    case
                        when Patient_CurrentStatusID in (1,4,6,7,8)
                            and (p1.name in ('TX- STAR Kids- AMERIGROUP','TX- AMERIGROUP STAR/CHIP')
                                or p2.name in ('TX- STAR Kids- AMERIGROUP','TX- AMERIGROUP STAR/CHIP')
                                or p3.name in ('TX- STAR Kids- AMERIGROUP','TX- AMERIGROUP STAR/CHIP'))
                        then 1
                        else 0
                        end
                WHERE 1=1";
        if($clientId != '') {
            $query = $query .$clientIdQueryConstant." ".$clientId;
        }
        $description = 'hh_eligible';
        $this->executeQuery($query, $description, $logFile);
    }

    private function executeQuery($query, $description, $logFile)
    {
        H::ExcelLog('Updating ' . $description . ': ' . $query, $logFile);
        echo $query . "\n";
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Updating ' . $description . ' was successful', $logFile);
                echo 'Query was successful' . "\n";
            }else{
                H::ExcelLog('Updating ' . $description . ' was not successful', $logFile);
                H::ExcelLog("There was some error", $logFile);
                echo "There was some error" . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog('Updating ' . $description . ' received an error', $logFile);
            H::ExcelLog('Error: ' . $e->getMessage(), $logFile);
        }
        sleep(2);
    }

}
