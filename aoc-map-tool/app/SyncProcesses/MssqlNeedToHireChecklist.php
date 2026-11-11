<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Helpers\Notifications;
use App\Notes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlNeedToHireChecklist
{

    public function ProcessNthRequisitions()
    {
        echo "Check for NTH records that need JobVite Requisition opened  \n";
        $syncType = 'nthRequisitions';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        H::ExcelLog('starting process to checkfor NTH records needing JobVite Requisition opened' . H::getHelper('Sync')->getSessionId(), 'nthRequisitions.log');
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'nthRequisitions.log');
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
        $rawData = DB::table('NeedToHire_Accounts')
            ->join('NeedToHire_Checklist','NeedToHire_Checklist.aoc_nthaccounts_id','=','NeedToHire_Accounts.id')
            ->join('mssql_patients','mssql_patients.id','=','NeedToHire_Accounts.aoc_patient_id')
            ->select('NeedToHire_Accounts.ServicesNeeded','mssql_patients.Patient_City','mssql_patients.Patient_State','NeedToHire_Checklist.updated_at',
                    'NeedToHire_Checklist.id','NeedToHire_Checklist.aoc_nthaccounts_id','mssql_patients.Patient_FirstName','mssql_patients.Patient_LastName',
                    'NeedToHire_Checklist.HUBcityStarted')
            ->where('NeedToHire_Accounts.IsActive', 1)
            ->where('NeedToHire_Checklist.HUBcity', 0)
            ->whereRaw("concat(NeedToHire_Checklist.updated_at,NeedToHire_Accounts.id) > '" . $combinedString . "'")
            ->orderBy(DB::raw('concat(NeedToHire_Checklist.updated_at,NeedToHire_Accounts.id)'),'asc')->limit($limit)->get();


        $batchCount = count($rawData);
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            $checklistId = $row['id'];
            $nthAccountId = $row['aoc_nthaccounts_id'];
            $last_updated_date = $row['updated_at'];
            $last_updated_id = $row['id'];
            var_dump($row);
            //find hub city
            $state = $row['Patient_State'];
            if($state=='TEXAS') {
                $state = 'TX';
            }
            if($state=='Florida') {
                $state = 'FL';
            }

            $categoryState = $state;
            if($state=='NC') {
                $categoryState = 'N Carolina';
            }
            if($state=='SC') {
                $categoryState = 'S Carolina';
            }
            $city = $row['Patient_City'];
            $services = $row['ServicesNeeded'];
            $patientName = $row['Patient_FirstName'] . ' ' . $row['Patient_LastName'];
            H::ExcelLog($patientName . ' - ' . $city . ', ' . $state . ' (' . $services . ')', 'nthRequisitions.log');
            //first make sure there is a hub city
            $hubCity = '';
            $hubRecord = DB::table('NeedToHire_Hub')->select('*')->where('ShortState',$state)->where('City',$city)->first();
            if(empty($hubRecord)){
                echo 'No Hub City Found.  Notifying Admin' . "\n";
                //send Natalie a notification and record as note
                $notificationText = 'Error:  The NTH record for ' . $patientName . ' (' . $city . ', ' . $state . ' does not match a valid Hub City.';
                $notification = DB::table('notifications')->select('*')->where('title',$notificationText)->where('type','NeedToHire')
                    ->where('related_item',$nthAccountId)
                    ->where('created_at','>', date('Y-m-d'))->first();
                if(empty($notification)){
                    //update record
                    $updatedData = ['HUBcityWorked' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                    if(is_null($row['HUBcityStarted'])) {
                        $updatedData['HUBcityStarted'] = date('Y-m-d H:i:s');
                    }
                    $updated = DB::table('NeedToHire_Checklist')->where('id', $checklistId)->update($updatedData);

                    Notifications::addNotification($data = ['user_id' => config('constant')['jobNotificationUserId'], 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                    Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                    echo $notificationText . ' notification sent to admin' . "\n";
                    H::ExcelLog($notificationText, 'nthRequisitions.log');
                }else{
                    echo 'Skipping Notification' . "\n";
                }
            }else{
                $hubCity = $hubRecord->HUB;

                //now make sure there is a valid service
                if($services=='PDN' || $services=='PDN/Respite' || $services=='Respite' || $services=='Skilled Nursing' || $services=='CNA'
                    || $services=='PNA' || $services=='IHSS'){
                    if($services=='PDN' || $services=='PDN/Respite' || $services=='Respite' || $services=='Skilled Nursing'){
                        echo 'Requisition for Nursing' . "\n";
                        H::ExcelLog("looking for where locationCity = '" . $hubCity . "' and category like '" . $categoryState . "%' and jobState = 'Open' and (title like '%RN / LPN%' or title like '%RN / LVN%')" , 'nthRequisitions.log');
                        $requisition = DB::table('jobs')->select('*')->where('locationCity',$hubCity)->where('category','LIKE',$categoryState . "%")->where('jobState','Open')
                        ->where(
                            function ($query) {
                                $query->where('title', 'LIKE','%RN / LPN%')->orWhere('title','LIKE', '%RN / LVN%');
                            }
                        )
                        ->first();
                    }elseif($services=='CNA' || $services=='PNA' || $services=='IHSS'){
                        echo 'Requisition for CNA' . "\n";
                        H::ExcelLog("looking for where locationCity = '" . $hubCity . "' and category like '" . $categoryState . "%' and jobState = 'Open' and title = 'CNA Pediatric Home Health" , 'nthRequisitions.log');
                        $requisition = DB::table('jobs')->select('*')->where('locationCity',$hubCity)->where('category','LIKE',$categoryState . "%")->where('jobState','Open')
                        ->where('title','CNA Pediatric Home Health')->first();
                    }
                    if(!empty($requisition)){
                        //mark checklist and record as note
                        $updatedData = ['HUBcity' => 1, 'HUBcityWorked' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                        if(is_null($row['HUBcityStarted'])) {
                            $updatedData['HUBcityStarted'] = date('Y-m-d H:i:s');
                        }
                        echo 'id: ' . $checklistId . "\n";
                        var_dump($updatedData);
                        echo "\n\n";
                        $updated = DB::table('NeedToHire_Checklist')->where('id', $checklistId)->update($updatedData);
                        $myNote = 'Job Requisition ' . $requisition->requisitionId . ' (' . $requisition->category . ' - ' . $requisition->title . ') was found for ' . $patientName;
                        $insert = Notes::insert(['entity' => 'NeedToHire', 'entity_id' => $nthAccountId, 'nth_id' => $nthAccountId,
                                'notes' => $myNote, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);

                        H::ExcelLog($myNote, 'nthRequisitions.log');
                    }else{
                        //send Natalie a notification if one hasn't been sent today
                        $notificationText = 'A job requisition is needed for ' . $services . ' in ' . $hubCity . ', ' . $state;
                        $notification = DB::table('notifications')->select('*')->where('title',$notificationText)->where('type','NeedToHire')
                            ->where('related_item',$nthAccountId)
                            ->where('created_at','>', date('Y-m-d'))->first();
                        if(empty($notification)){
                            //update record
                            $updatedData = ['HUBcityWorked' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                            if(is_null($row['HUBcityStarted'])) {
                                $updatedData['HUBcityStarted'] = date('Y-m-d H:i:s');
                            }
                            $updated = DB::table('NeedToHire_Checklist')->where('id', $checklistId)->update($updatedData);

                            Notifications::addNotification($data = ['user_id' => config('constant')['jobNotificationUserId'], 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                            Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                            H::ExcelLog($notificationText, 'nthRequisitions.log');
                        }else{
                            echo 'Skipping notification' . "\n";
                        }
                    }
                }else{
                    echo 'Invalid Service listed.  Notifying Admin' . "\n";
                    //send Natalie a notification
                    $notificationText = 'Error:  The NTH record for ' . $patientName . ' (' . $city . ', ' . $state . ' does not have a valid service listed.';
                    $notification = DB::table('notifications')->select('*')->where('title',$notificationText)->where('type','NeedToHire')
                        ->where('related_item',$nthAccountId)
                        ->where('created_at','>', date('Y-m-d'))->first();
                    if(empty($notification)){
                        //update record
                        $updatedData = ['HUBcityWorked' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
                        if(is_null($row['HUBcityStarted'])) {
                            $updatedData['HUBcityStarted'] = date('Y-m-d H:i:s');
                        }
                        $updated = DB::table('NeedToHire_Checklist')->where('id', $checklistId)->update($updatedData);

                        Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                        Notifications::addNotification($data = ['user_id' => config('constant')['jobNotificationUserId'], 'title' => $notificationText, 'type' => 'NeedToHire', 'related_item' => $nthAccountId]);
                        echo $notificationText . ' notification sent to admin' . "\n";
                        H::ExcelLog($notificationText, 'nthRequisitions.log');
                    }else{
                        echo 'Skipping Notification' . "\n";
                    }
                }
            }

            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'nthRequisitions.log');
    }

    public function updateNthHistoricalData()
    {
        echo "Insert all active NTH accounts into NeedToHire_Historical table  \n";
        $query = "insert into  `NeedToHire_Historical` (aoc_nthaccounts_id, IsActive, ShiftHours, CreatedReason, RemovalReason, nth_date, created_at)
                        select id, IsActive,
                            ifnull((select sum(ShiftHours) from NeedToHire_Schedules where
                                NeedToHire_Schedules.aoc_nthaccounts_id = NeedToHire_Accounts.id and NeedToHire_Schedules.IsActive = 1),0)
                            ,CreatedReason, RemovalReason, CURRENT_DATE, NOW()
                        from NeedToHire_Accounts
                        where NeedToHire_Accounts.IsActive = 1";
        H::ExcelLog('Running Update NeedToHire_Historical query', 'nthHistorical.log');
        echo 'Running Update NeedToHire_Historical query' . "\n";
        echo $query . "\n";
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Query was successful', 'nthHistorical.log');
                echo 'Query was successful' . "\n";
            }else{
                H::ExcelLog('There was some error', 'nthHistorical.log');
                echo 'There was some error' . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog('Error: ' . $e->getMessage(), 'nthHistorical.log');
        }
    }

    public function fixNthMapSchedulesoriginal()
    {
        echo "Fix Nth map schedules  \n";
        $syncType = 'fixNthMap';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'nthMapFix.log');
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";
        $limit = $batch_info['batch_size'];
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        if(trim($last_updated_id)=='') {
            $last_updated_id = 0;
        }
        $rawData = DB::select("select * from maps_nth where IsActive = 1 order by id");
        $batchCount = count($rawData);
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            //update schedules data
            $nthaccountid = $row['aoc_nthaccounts_id'];
            echo 'fixing: ' . $row['id'] . ' => ' . $nthaccountid . ' ' . $row['NTH_Hours'] . ' - ' . $row['Schedule'] .  "\n";
            H::getHelper('NTHChecklist')->setNTHScheduleToMap($nthaccountid);
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $row['id']);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'nthMapFix.log');
    }
    public function fixNthMapSchedules()
    {
        echo "Fix Nth schedule audit  \n";
        $syncType = 'fixNthMap';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'nthMapFix.log');
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";
        $limit = $batch_info['batch_size'];
        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        if(trim($last_updated_id)=='') {
            $last_updated_id = 0;
        }
        $rawData = DB::select("select id, schedule_id, created_at, StartDayOfWeek, StartHour, EndHour ,EndMinute, ShiftHours, Type, Reason, HoursAdded, HoursRemoved, NetChangeinHours, isNet
                        from NeedToHire_Audit where Type = 'R' and id > " . $last_updated_id . " order by id limit " . $limit);
        $batchCount = count($rawData);
        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            //update schedules data
            var_dump($row);
            echo "\n";
            $scheduleId = $row['schedule_id'];
            $removeId = $row['id'];
            $myDate = substr($row['created_at'],0,10);
            //see if it's a valid reason
            $reason = $row['Reason'];
            if(!in_array($reason,['Staffed', 'Client match - partial', 'Non-admit','Discharged','Discharged [ClientLevel]','Staffed [ClientLevel]','Non-admit [ClientLevel]',
                            'Clinician - Staffed','AOC - Staffed','Caregiver - Staffed'])){
                //find a matching schedule with 'A' and see if it's the same date
                $query = "select id, schedule_id, created_at, StartDayOfWeek, StartHour, EndHour ,EndMinute, ShiftHours, Type,
                Reason, HoursAdded, HoursRemoved, NetChangeinHours, isNet from NeedToHire_Audit
                where Type = 'A' and schedule_id = " . $scheduleId . " and cast(created_at as DATE) = '" . $myDate . "' limit 1";
                echo $query . "\n";
                $addRecord = DB::select($query);

                $myRecord = json_decode(json_encode($addRecord), true);
                foreach ($myRecord as $addRecord){
                    //mark additional record as isNet
                    $id = $addRecord['id'];
                    var_dump($addRecord);
                    echo "\n";
                    echo 'mark ' . $id . ' as isNet = 1' . "\n";
                    DB::table("NeedToHire_Audit")->where("id",$id)->update(['isNet' => 1,'ChangeDescription' => 'Net out to ' . $removeId]);
                    H::ExcelLog('Set schedule ' . $scheduleId . ' audit ' . $id . ' to net out to ' . $removeId,'fixNthAudit.log');
                    H::ExcelLog(print_r($row,true),'fixNthAudit.log');
                    H::ExcelLog('Set schedule ' . $scheduleId . ' audit ' . $removeId . ' to net out to ' . $id,'fixNthAudit.log');
                    H::ExcelLog(print_r($addRecord,true),'fixNthAudit.log');
                    echo 'mark ' . $removeId . ' as isNet = 1' . "\n";
                    DB::table("NeedToHire_Audit")->where("id",$removeId)->update(['isNet' => 1,'ChangeDescription' => 'Net out to ' . $id]);
                }
            }
            if(in_array($reason,['Ticket Made in Error','Ticket made in error [ClientLevel]','AOC - Ticket Made in Error'])){
                //find a matching schedule with 'A' and see if it's the same date
                $addRecord = DB::select("select id, schedule_id, created_at, StartDayOfWeek, StartHour, EndHour ,EndMinute, ShiftHours, Type, Reason, HoursAdded,
                            HoursRemoved, NetChangeinHours, isNet
                            from NeedToHire_Audit where Type = 'A' and schedule_id = " . $scheduleId . " limit 1");
                $myRecord = json_decode(json_encode($addRecord), true);
                foreach ($myRecord as $addRecord){
                    //mark additional record as isNet
                    $id = $addRecord['id'];
                    var_dump($addRecord);
                    echo "\n";
                    echo 'mark ' . $id . ' as isNet = 1' . "\n";
                    DB::table("NeedToHire_Audit")->where("id",$id)->update(['isNet' => 1,'ChangeDescription' => 'Net out to ' . $removeId]);
                    H::ExcelLog('Set schedule ' . $scheduleId . ' audit ' . $id . ' to net out to ' . $removeId,'fixNthAudit.log');
                    H::ExcelLog(print_r($row,true),'fixNthAudit.log');
                    H::ExcelLog('Set schedule ' . $scheduleId . ' audit ' . $removeId . ' to net out to ' . $id,'fixNthAudit.log');
                    H::ExcelLog(print_r($addRecord,true),'fixNthAudit.log');
                    echo 'mark ' . $removeId . ' as isNet = 1' . "\n";
                    DB::table("NeedToHire_Audit")->where("id",$removeId)->update(['isNet' => 1,'ChangeDescription' => 'Net out to ' . $id]);
                }
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $row['id']);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'nthMapFix.log');
    }
}
