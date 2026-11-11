<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class MssqlRecruiterTracker
{

    public function syncTrackerToMssql()
    {
        echo "Sync RecruiterTracker data back to mssql \n";
        $syncType = 'mssql-tracker';
        $tableName = 'mssql_recruitertracker';
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
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $rawData = DB::table($tableName)->select('id','mssqlID','aoc_patient_id','Candidate','created_at','updated_at')
            ->whereRaw("concat(updated_at,id) > '" . $combinedString . "'")
            ->orderBy(DB::raw('concat(updated_at,id)'),'asc')->limit($limit)->get();

        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            var_dump($row);
            echo "\n";
            $tracker_id = $row['id'];
            $last_updated_date = $row['updated_at'];
            $last_updated_id = $row['id'];

            echo 'TrackerId: ' . $tracker_id . "\n";
            //first get $aoc_patient_id
            $aoc_patient_id = $row['aoc_patient_id'];
            $aoc_patient_id_2 = '';
            echo 'patient: ' . $aoc_patient_id . "\n";
            $schedules = DB::table('recruiting_schedule')->select('id','aoc_patient_id')->where("tracker_id",$tracker_id)->get();
            $schedules = json_decode(json_encode($schedules),true);
            $patients = [];
            foreach($schedules as $sched){
                echo 'sched patient: ' . $sched['aoc_patient_id'] . "\n";
                if($aoc_patient_id!=$sched['aoc_patient_id']){
                    if($aoc_patient_id==''){
                        $aoc_patient_id = $sched['aoc_patient_id'];
                    }else{
                        if($aoc_patient_id_2==''){
                            $aoc_patient_id_2 = $sched['aoc_patient_id'];
                        }else{
                            H::ExcelLog('ERROR WITH PATIENTS: ' . $tracker_id,'recruiterTrackerUpdate.log');
                        }
                    }
                }
            }
            $result = H::getHelper('Sync')->UpdateTrackerToMssql($tracker_id,'mssql',$aoc_patient_id,0);
            echo "Result: " . "\n";
            var_dump($result);
            echo "\n\n\n";
            if($aoc_patient_id_2!=''){
                echo 'Now update 2nd patient';
                H::ExcelLog('updating 2nd patient: ' . $aoc_patient_id_2,'recruiterTrackerUpdate.log');
                $result = H::getHelper('Sync')->UpdateTrackerToMssql($tracker_id,'mssql',$aoc_patient_id_2,1);
                echo "Result: " . "\n";
                var_dump($result);
                echo "\n\n\n";
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    /**
     * Sync recruiterTracker data - this is reset every morning
     * when checkETL returns a new successful job,  we reset process_settings for coldCall and textBlast and morningNTH and set
     * records in mssql_recruiterTracker are set to is_updated = 0.
     */
    public function tmpSyncRecruiterTracker()
    {
        echo "Sync RecruiterTracker data \n";
        $syncType = 'tmptracker';
        $tableName = 'mssql_recruitertracker_fromsql';
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
        $tsql = 'SELECT top ' . $limit . " * FROM Excelss.RecruiterTracker
			WHERE [ID]  > '$last_updated_id'
			ORDER BY [ID] ASC";

        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;

        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row['ID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['ID'];
            $row['mssqlID'] = $uniqueID;
            unset($row['ID']);
            unset($row['CCRRequestDate']);
            //prepare row for insertion
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                if ($key == 'IPEATime') {
                    $key = 'IPEA_Time';
                }
                $newKey = str_replace(' ', '_', $key);
                $newKey = str_replace('%', '', $key);
                $newRow[$newKey] = $value;
            }

            $newRow['is_updated'] = '1';
            $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            $tracker_id = 0;
            $dbClient = DB::select("select * from $tableName where `mssqlID` = '" . $uniqueID . "'");

            if (!empty($dbClient)) {
                $tracker_id = $dbClient[0]->id;
            }

            if (!empty($dbClient)) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw('`mssqlID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $tracker_id = DB::table($tableName)->insertGetId($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);

        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }


    /**
     * Sync recruiterTracker data - this is reset every morning
     * when checkETL returns a new successful job,  we reset process_settings for coldCall and textBlast and morningNTH and set
     * records in mssql_recruiterTracker are set to is_updated = 0.
     */
    public function OLDsyncRecruiterTrackerOLD()
    {
        echo "Sync RecruiterTracker data \n";
        $syncType = 'tracker';
        $tableName = 'mssql_recruitertracker';
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
        $tsql = 'SELECT top ' . $limit . " * FROM Excelss.RecruiterTracker
			WHERE [ID]  > '$last_updated_id'
			ORDER BY [ID] ASC";

        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        //calculate date of monday this week - for schedules
        $day = date('w') - 1;
        $week_start = date('Y-m-d', strtotime('-' . $day . ' days'));
        $schedules = ['filler', 'IPEA_Scheduled', 'Clinical_Competency_Review', 'PEPI_Scheduled', 'WEPI_Scheduled', 'Client_Match_Date', 'Candidate_Start_Date', 'Training_Shift', 'School_Set_Up_Due_Date'];

        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row['ID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['ID'];
            $row['mssqlID'] = $uniqueID;
            unset($row['ID']);
            unset($row['CCRRequestDate']);
            //prepare row for insertion
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                if ($key == 'IPEATime') {
                    $key = 'IPEA_Time';
                }
                $newKey = str_replace(' ', '_', $key);
                $newKey = str_replace('%', '', $key);
                $newRow[$newKey] = $value;
            }

            $newRow['is_updated'] = '1';
            $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            $tracker_id = 0;
            $dbClient = DB::select("select * from $tableName where `mssqlID` = '" . $uniqueID . "'");

            if (!empty($dbClient)) {
                $tracker_id = $dbClient[0]->id;
            }
            $tmp = $row['Branch_Location'];
            if ($tmp == '') {
                $tmp = 'NA';
            }
            $whiteBoard = substr($tmp, 0, 2);
            if ($row['Skillset'] == 'LPN' || $row['Skillset'] == 'LVN' || $row['Skillset'] == 'RN') {
                $whiteBoard .= ' - PDN';
            } else {
                $whiteBoard .= ' - ' . $row['Skillset'];
            }
            $newRow['whiteboard_row'] = $whiteBoard;
            //get user
            $userId = 0;
            $newRow['user_id'] = '';
            $recruiter = trim($row['Recruiter']);
            if ($recruiter != '') {
                $dbUser = DB::select("select id from users where `name` = '$recruiter'");
                echo 'count: ' . count($dbUser);
                echo "\n";
                if (count($dbUser) > 0) {
                    $userId = $dbUser[0]->id;
                    $newRow['user_id'] = $userId;
                }
            }
            //get provider
            $newRow['aoc_provider_id'] = 0;
            $tmpid = trim($row['Clinician_ID']);
            $tmp = addslashes($row['Candidate']);
            $clinicianId = $tmpid;
            if ($tmpid != '') {
                $dbUser = DB::select("SELECT * FROM `mssql_providers` WHERE ClinicianID = '$tmpid' and '$tmp' like CONCAT('% ', LastName)");
                if (count($dbUser) > 0) {
                    $newRow['aoc_provider_id'] = $dbUser[0]->id;
                    $newRow['no_provider_match'] = 0;
                } else {
                    if ($row['IPEA_Date'] > '2022-01-01') {
                        if ($tracker_id != 0) {
                            if ($dbClient[0]->aoc_provider_id == 0) {
                                $newRow['no_provider_match'] = 1;
                            }
                        } else {
                            $newRow['no_provider_match'] = 1;
                        }
                    }
                }
            }
            //get candidate
            $newRow['aoc_candidate_id'] = 0;
            $tmp = trim($row['Candidate']);
            $tmp2 = substr($row['Date_Contacted'], 0, 10);
            if ($tmp != '') {
                $dbUser = DB::table('candidates')->select('id')->where('fullName', $tmp)->where('application_date', $tmp2)->get();
                if (count($dbUser) > 0) {
                    $newRow['aoc_candidate_id'] = $dbUser[0]->id;
                    $newRow['no_candidate_match'] = 0;
                } else {
                    if ($clinicianId == '' && $row['IPEA_Date'] > '2022-01-01') {
                        $newRow['no_candidate_match'] = 1;
                    }
                }
            }
            //get client
            $newRow['aoc_patient_id'] = 0;
            $tmpid = trim($row['PatientID']);
            if ($tmpid != '') {
                $dbUser = DB::select("select id from mssql_patients where `PatientID` = ? AND IsLatestAdmit = 1", [$tmpid]);
                if (count($dbUser) > 0) {
                    $newRow['aoc_patient_id'] = $dbUser[0]->id;
                }
            }
            //get facility
            $newRow['aoc_facility_id'] = 0;
            $tmpid = trim($row['FacilityID']);
            $branchId = 0;
            if ($tmp != '') {
                $dbUser = DB::select("select id, BranchName from mssql_facilities where `FacilityID` = '$tmpid'");
                if (count($dbUser) > 0) {
                    $newRow['aoc_facility_id'] = $dbUser[0]->id;
                    $branchName = $dbUser[0]->BranchName;
                    $dbUser2 = DB::select("select id from branch_locations where `branch` = '$branchName'");
                    if (count($dbUser2) > 0) {
                        $branchId = $dbUser2[0]->id;
                    }
                }
            }

            if (!empty($dbClient)) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw('`mssqlID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $tracker_id = DB::table($tableName)->insertGetId($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);

            //now see if this user/whiteboard_row combination is in whiteboard table,  if not - add it
            if ($userId != 0) {
                //see if this record has dates in this week
                if ($row['IPEA_Scheduled'] > $week_start || $row['PEPI_Scheduled'] > $week_start || $row['WEPI_Scheduled'] > $week_start ||
                                $row['Client_Match_Date'] > $week_start || $row['Candidate_Start_Date'] > $week_start) {
                    $dbClient = DB::select("select id from `whiteboard` where `user_id` = '$userId' and `row_id` = '$whiteBoard'");
                    if (!empty($dbClient)) {
                        echo 'Whiteboard Record Exists' . "\n";
                    } else {
                        $wbRow = ['user_id' => $userId, 'user_name' => $row['Recruiter'], 'row_number' => $userId, 'row_id' => $whiteBoard, 'territory' => 'Out of Territory',
                                        'candidate_start_hour_goals' => 0, 'ipea_goal' => 0, 'pepi_wepi_goal' => 0, 'client_match_goal' => 0, 'client_match_hours_goal' => 0,
                                        'candidate_start_goal' => 0, ];
                        DB::table('whiteboard')->insert($wbRow);
                    }
                }
            }
            //now insert Candidate_Notes into notes table
            if($newRow['Candidate_Notes']!=''){
                $newAppt = ['entity' => 'tracker', 'entity_id' => $tracker_id, 'notes' => $newRow['Candidate_Notes'], 'provider_id' => $newRow['aoc_provider_id'],
                            'candidate_id' => $newRow['aoc_candidate_id'], 'patient_id' => $newRow['aoc_patient_id'], 'tracker_id'=> $tracker_id, 'created_by' => $newRow['user_id'],
                            'completed' => 1, 'completed_at' => date_format(date_create(), 'Y-m-d H:i:s'), 'completed_by' => $newRow['user_id'], 'priority' => 0,
                            'created_at' => date_format(date_create(), 'Y-m-d H:i:s'), 'updated_at' => date_format(date_create(), 'Y-m-d H:i:s')];
                //insert record
                DB::table('notes')->insert($newAppt);
            }

            //now add or update the schedules to recruiting_schedules table
            foreach ($schedules as $type => $sched) {
                echo $type . ' - ' . $sched . ' - ' . $tracker_id . "\n";
                if ($type != 0) {
                    $schedDate = substr($row[$sched], 0, 10);
                    if($type == 6) {
                        $schedDate = substr($row['Client_Match_Date'], 0, 10);
                    }
                    echo htmlspecialchars($row[$sched], ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($schedDate, ENT_QUOTES, 'UTF-8') . "\n";
                    if ($schedDate != '') {
                        //insert record
                        $apptTime = '';
                        if ($type == 1) {
                            $apptTime = $newRow['IPEA_Time'];
                        }
                        if ($type == 3) {
                            $apptTime = $newRow['PEPI_Time'];
                        }
                        if ($type == 4) {
                            $apptTime = $newRow['WEPI_Time'];
                        }

                        $apptDate = $schedDate;
                        if ($type == 1) {
                            $apptDate = $newRow['IPEA_Date'];
                        }
                        if ($type == 3) {
                            $apptDate = $newRow['PEPI_Date'];
                        }
                        if ($type == 4) {
                            $apptDate = $newRow['WEPI_Date'];
                        }

                        $outcome = '';
                        if ($type == 1) {
                            $outcome = $newRow['IPEA_Outcome'];
                        }
                        if ($type == 3) {
                            $outcome = $newRow['PEPI-Outcome'];
                        }
                        if ($type == 4) {
                            $outcome = $newRow['WEPI-Outcome'];
                        }
                        $ipeaLocationId = 0;
                        if($type == 1) {
                            $ipeaLocationId = $branchId;
                        }
                        $newAppt = ['tracker_id'=> $tracker_id, 'type_id' => $type, 'appointment_date' => $apptDate, 'appointment_time' => $apptTime,
                                'aoc_patient_id' => $newRow['aoc_patient_id'], 'aoc_provider_id' => $newRow['aoc_provider_id'], 'outcome' => $outcome,
                                'aoc_candidate_id' => $newRow['aoc_candidate_id'], 'aoc_facility_id' => $newRow['aoc_facility_id'], 'ipea_location_id' => $ipeaLocationId,
                                'pay_rate' => $newRow['Agreed_Pay_Rate'], 'gp' => $newRow['GP'], 'scheduled_date' => $schedDate, 'is_updated' => 1, 'updated_at' => date_format(date_create(), 'Y-m-d H:i:s'),
                            ];
                        //insert record
                        if($type!=5){
                            echo 'insert record' . "\n";
                            DB::table('recruiting_schedule')->insert($newAppt);
                        }
                    }
                }
            }
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
            $deleted = DB::table($tableName)->where('is_updated', 0)->delete();
            $deleted = DB::table('recruiting_schedule')->where('is_updated', 0)->delete();
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    public function MatchClinicianID()
    {
        echo "Match Clinician ID from mssql_providers to mssql_recruitertracker  \n";
        $syncType = 'matchClinicianID';
        $tableName = 'mssql_recruitertracker';
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

        $limit = $batch_info['batch_size'];
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        echo $combinedString . "\n";
        $rawData = DB::table($tableName)->select('id', 'Candidate', 'Clinician_ID', 'aoc_provider_id', 'updated_at')
                ->whereRaw("ifnull(Clinician_ID,'') <> '' and ifnull(aoc_provider_id,0) = 0")
                ->where('is_active',1)->get();

        $batchCount = count($rawData);
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            var_dump($row);
            echo "\n";
            $id = $row['id'];
            $last_updated_date = $row['updated_at'];
            $last_updated_id = $id;
            //see if we need to match the ClinicianID to record in providers
            if ($row['Clinician_ID'] != '' && $row['aoc_provider_id'] == 0) {
                H::ExcelLog($row['Candidate'] . ' Trying to match ' . $row['Clinician_ID'] . ' to a provider.','matchTrackerToClinician.log');
                $clinicianId = $row['Clinician_ID'];
                echo 'matching ' . $clinicianId . "\n";
                $dbRecord = DB::table('mssql_providers')->select('id')->where('ClinicianID', $clinicianId)->first();
                var_dump($dbRecord);
                echo "\n";
                if (!empty($dbRecord)) {
                    echo 'found!' . "\n";
                    $aoc_provider_id = $dbRecord->id;
                    $updateRT = [];
                    $updateRT['aoc_provider_id'] = $aoc_provider_id;
                    H::ExcelLog('Updateding tracker ' . $id,'matchTrackerToClinician.log');
                    H::ExcelLog(print_r($updateRT,true),'matchTrackerToClinician.log');
                    DB::table('mssql_recruitertracker')->where('id', $id)->limit(1)->update($updateRT);
                } else {
                    echo 'notfound' . "\n";
                    //do some kind of notification!!!!!!
                }
            } else {
                echo 'skip' . "\n";
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    public function FixSchedules()
    {
        echo 'fix lynn';
        H::ExcelLog('fix lynn','fixlynn.log');
    }

    public function FixRecruitingSchedules()
    {
        echo "Fix Schedules that have been added when they should have been rescheduled  \n";
        $syncType = 'fixSchedules';
        $tableName = 'recruiting_schedule';
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

        $limit = $batch_info['batch_size'];
        $limit = 500;
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        echo $combinedString . "\n";
        $data = DB::table($tableName)->select('*')->where('id', '>', $last_updated_id)->orderBy('id')->limit($limit)->get();

        $batchCount = count($data);

        //start processing records
        $data = json_decode(json_encode($data), true);
        foreach ($data as $row) {
            //only process if not already marked as deleted!!!
            $processRecord = DB::table($tableName)->select('is_deleted')->where('id', $row['id'])->first();
            if($processRecord->is_deleted==0){
                $id = $row['id'];
                $last_updated_date = $row['updated_at'];
                $last_updated_id = $id;
                $tracker = DB::table('mssql_recruitertracker')->select('*')->where('id',$row['tracker_id'])->first();
                //see if this tracker/patient combination has a LATER record and if so,  update the appointment date and time from that record.  then mark that record as is_deleted
                $dbRecord = DB::table($tableName)->select('*')
                    ->where('type_id', $row['type_id'])
                    ->where('aoc_patient_id',$row['aoc_patient_id'])
                    ->where('tracker_id', $row['tracker_id'])
                    ->where('id', '>', $row['id'])
                    ->orderBy('id', 'asc')
                    ->get();
                if (count($dbRecord)>0) {
                    foreach($dbRecord as $sched){
                        echo "\n" . htmlspecialchars($tracker->Candidate, ENT_QUOTES, 'UTF-8') . ' for ' .
                        htmlspecialchars($tracker->Client_Name, ENT_QUOTES, 'UTF-8') . ' (' .
                        htmlspecialchars($row['type_id'], ENT_QUOTES, 'UTF-8') . ') - Setting ' .
                        htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . ' to ' .
                        htmlspecialchars($sched->appointment_date, ENT_QUOTES, 'UTF-8') . ' ' .
                        htmlspecialchars($sched->appointment_time, ENT_QUOTES, 'UTF-8') . ' (' .
                        htmlspecialchars($sched->id, ENT_QUOTES, 'UTF-8') . ')';
                        echo "\n";
                        //set the offical (first) record to appointment date and time of this one . . . . it will ultimately end up with the last match.
                        H::ExcelLog($tracker->id . ' ' . $tracker->Candidate . ' for ' . $tracker->Client_Name . ' (' . $row['type_id'] . ')  - changing ' . $id . ' from ' . $row['appointment_date'] . ' - ' . $row['appointment_time'] . ' to ' . $sched->appointment_date . ' - ' . $sched->appointment_time . ' (' . $sched->id . ')','fixRS.log');
                        DB::table($tableName)->where('id', $id)->limit(1)->update(['appointment_date' => $sched->appointment_date, 'appointment_time' => $sched->appointment_time]);
                        //now set this record as is_deleted = 1
                        DB::table($tableName)->where('id', $sched->id)->limit(1)->update(['is_deleted'=>1]);
                        H::ExcelLog('--- Marked ' . $sched->id . ' as deleted','fixRS.log');
                    }
                } else {
                    echo '.';
                }
            }else{
                echo $row['id'] . ' - marked as deleted' . "\n";
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    public function UpdateHoursWorked()
    {
        echo "Update hours worked for trackers in First Shift Confirmed or Bonus_Status = Paid and Hours_Worked < 40 \n";
        $syncType = 'updateHoursWorked';
        $tableName = 'mssql_recruitertracker';
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

        $limit = $batch_info['batch_size'];
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        if ($last_updated_id == 'donefortheday') {
            echo 'Skipping because we are done for the day';
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }

        $rawData = DB::table($tableName)
                ->leftJoin('mssql_patients', 'mssql_recruitertracker.aoc_patient_id', '=', 'mssql_patients.id')
                ->leftJoin('mssql_providers', 'mssql_recruitertracker.aoc_provider_id', '=', 'mssql_providers.id')
                ->select('mssql_recruitertracker.id', 'mssql_recruitertracker.Hours_Worked', 'mssql_recruitertracker.Candidate_Current_Status',
                        'mssql_recruitertracker.Bonus_Status', 'mssql_patients.PatientID', 'mssql_providers.ProviderID', 'mssql_recruitertracker.updated_at')
				->where('mssql_patients.not_in_kantime', '<>', 1)
                ->where('mssql_recruitertracker.id', '>', $last_updated_id)
                ->where('mssql_recruitertracker.Bonus_Status', '<>', 'Paid Out')
                ->whereRaw('ifnull(mssql_recruitertracker.Hours_Worked,0)<40')
                ->where(function($query) {
                    $query->where('mssql_recruitertracker.Candidate_Current_Status','Client Match')->orWhere('mssql_recruitertracker.Candidate_Current_Status','First Shift Confirmed');
                })
                ->orderBy('mssql_recruitertracker.updated_at', 'asc')
                ->orderBy('mssql_recruitertracker.id', 'asc')
                ->limit($limit)->get();

        $batchCount = count($rawData);
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            var_dump($row);
            echo "\n";
            H::ExcelLog(print_r($row,true),'updateHoursWorked.log');
            $id = $row['id'];
            $last_updated_date = $row['updated_at'];
            $last_updated_id = $id;
            //see if we need to match the ClinicianID to record in providers
            if ($row['PatientID'] != '' && $row['PatientID'] != 0 && $row['ProviderID'] != '' && $row['ProviderID'] != 0) {
                echo 'get hours from ScheduleMaster' . "\n";
                $totalHours = DB::table('mssql_schedulemaster')
                    ->where('ProviderID', $row['ProviderID'])
                    ->where('PatientID', $row['PatientID'])
                    ->whereRaw("ServiceName NOT LIKE '%Coverage%'")
                    ->where('ScheduleStatusID', '<>', 4)
                    ->where('isBillable', 1)
                    ->sum('ActualHours');
                var_dump($totalHours);
                echo "\n";
                if ($totalHours > 0 && $totalHours != $row['Hours_Worked']) {
                    echo 'Hours found!' . "\n";
                    if($totalHours > 40) {
                        $totalHours = 40;
                    }
                    $updateTracker = [];
                    $updateTracker['Hours_Worked'] = $totalHours;
                    DB::table('mssql_recruitertracker')->where('id', $id)->limit(1)->update($updateTracker);
                    H::ExcelLog('Hours_Worked updated from ' . $row['Hours_Worked'] . ' to ' . $totalHours,'updateHoursWorked.log');
                    if($totalHours==40) {
                        H::ExcelLog('Bonus_Status updated from ' . $row['Bonus_Status'] . ' to Submit for Approval','updateHoursWorked.log');
                    }
                } else {
                    echo 'skipped because hours did not change' . "\n";
                    H::ExcelLog('skipped because hours did not change','updateHoursWorked.log');
                }
            } else {
                echo 'skip due to bad data!!!!' . "\n";
                H::ExcelLog('ERROR with Patient or Provider ID','updateHoursWorked.log');
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($batchCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    public function updateIpeaRescheduledToBlank(){
        DB::table('mssql_recruitertracker')->where('live_ipea_status','Rescheduled')->where('IPEA_Date','<=',date('Y-m-d'))->update(['live_ipea_status'=>'']);
    }
}
