<?php

namespace App\SyncProcesses;

use App\Enums\MssqlEmploymentStatus;
use App\Events\MssqlRecruiterTracker\MssqlRecruiterTrackerOutdated;
use App\Events\Providers\ProvidersBatchSynced;
use App\Helpers\H;
use App\Services\Pdf\Therapy\TherapyPdfService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Helpers\Notifications;
use App\Notes;
use Mail;
use App\Candidates;

class MssqlProviders
{
private const BATCH_SIZE_FOR_CANDIDATES = 100;
    /**
     * Sync Providers.
     */
    public function syncProviders()
    {
        echo "Sync Provider List \n";
        $syncType = 'providers';
        $mssqlTableName = '[Excelss].[GENERAL_Providers]';
        $tableName = 'mssql_providers';
        $fieldName = 'BillingProviderID';
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

			echo 'process post sync stuff';

            //Send email notification to check for the new hires for the renewables: Nik
            /*Mail::send([], [], function ($message) {

                $msg = "Providers Sync finished. Please check new hires for the renewables";
                $fromName  =  'Angels Of Care';
                $fromEmail  =  env('MAIL_FROM_ADDRESS');
                $message->from($fromEmail,$fromName);
                $message->subject('Check New hires for Renewables');
                $message->to(env('SYNC_SUPPORT_MAIL_ADDRESS'));
                $message->html($msg);
            });*/
			//Set daas_renewables sync last updated date to 2 days back: Nik
            $renewableSyncFlag = true;
            while($renewableSyncFlag) {
                $renewableBatchInfo = DB::table('process_settings')->select('running_now')->where('process', '=', 'daas_renewables')->limit(1)->get();
                if($renewableBatchInfo[0]->running_now == 0) {
                    $renewablesLastUpdatedDate = date('Y-m-d',strtotime("-2 days")) . " 00:00:01";
			        H::getHelper('Sync')->update_last_updated('daas_renewables', $renewablesLastUpdatedDate, 0);
                    $renewableSyncFlag = false;
                }
                sleep(10);
            }


            $this->matchToTrackers();
            $query = "update `aoc_documents`
                left join mssql_recruitertracker rt on tracker_id = rt.id
                set entity_type = 'provider', entity_id = rt.aoc_provider_id
                WHERE entity_type = 'candidate' and document_type in ('pay_rate','offer_accepted','neo_attachment') and ifnull(rt.aoc_provider_id,0) <> 0;";
            try{
                $result = DB::statement($query);
                if($result){
                    H::ExcelLog('Query was successful', 'assignPayrateAgreeToProvider.log');
                    echo 'Query was successful' . "\n";
                }else{
                    H::ExcelLog('There was some error', 'assignPayrateAgreeToProvider.log');
                    echo 'There was some error' . "\n";
                    //send a notification
                }
            }catch (\Exception $e){
                H::ExcelLog('Error: ' . $e->getMessage(), 'assignPayrateAgreeToProvider.log');
            }

			$query1 = "update users u
                left join mssql_providers p on u.email = p.AOC_Email
                set u.phone_number = p.Mobile";
            try{
                $result = DB::statement($query1);
                if($result){
                    H::ExcelLog('Query was successful', 'updateUserPhoneNumber.log');
                    echo 'Query was successful' . "\n";
                }else{
                    H::ExcelLog('There was some error', 'updateUserPhoneNumber.log');
                    echo 'There was some error' . "\n";
                    //send a notification
                }
            }catch (\Exception $e){
                H::ExcelLog('Error: ' . $e->getMessage(), 'updateUserPhoneNumber.log');
            }

            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday2');
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        if ($last_updated_id == 'donefortheday2') {
            echo 'skipping because we are done for the day';
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

        $providerIds = [];
        $batchSize = self::BATCH_SIZE_FOR_CANDIDATES;
        $counter = 0;

        $rows = [];
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            $rows[] = $row;
        }

        // Batch-fetch what used to be looked up per row (N+1) - one round trip per chunk instead of one per provider.
        // batch_size comes from process_settings and has no upper bound, so whereIn() is chunked rather than
        // run against the whole batch at once.
        $billingProviderIds = array_column($rows, $fieldName);
        $idChunks = array_chunk($billingProviderIds, 500);

        $latestScheduleByProviderId = collect();
        foreach ($idChunks as $idChunk) {
            $latestScheduleByProviderId = $latestScheduleByProviderId->union(
                DB::table('mssql_schedulemaster')
                    ->whereIn('ProviderID', $idChunk)
                    ->whereIn('ScheduleStatusID', [1, 2, 3])
                    ->where('isMiscVisit', 0)
                    ->groupBy('ProviderID')
                    ->selectRaw('ProviderID, MAX(ScheduleDate) as ScheduleDate')
                    ->get()
                    ->keyBy('ProviderID')
            );
        }

        $existingProvidersById = collect();
        foreach ($idChunks as $idChunk) {
            $existingProvidersById = $existingProvidersById->union(
                DB::table($tableName)->whereIn($fieldName, $idChunk)->get()->keyBy($fieldName)
            );
        }

        foreach ($rows as $row) {
            var_dump($row);
            echo "\n";
            H::ExcelLog(print_r($row,true),'syncProviders.log');
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row[$fieldName];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            //get latest billable shift
            $latestVisit = $latestScheduleByProviderId->get($uniqueID);
            if ($latestVisit) {
                $newRow['has_billable_visit'] = 1;
                $newRow['latest_schedule'] = $latestVisit->ScheduleDate;
                echo 'schedule: ' . $latestVisit->ScheduleDate . "\n";
                H::ExcelLog('Billable Visit: ' . $latestVisit->ScheduleDate,'syncProviders.log');
            }else{
                echo 'schedule not found for ' . $row['ProviderID'] . "\n";
                $newRow['has_billable_visit'] = 0;
                H::ExcelLog('No billable visit','syncProviders.log');
            }
            $existingProvider = $existingProvidersById->get($uniqueID);
            $dbRecord = $existingProvider ? [$existingProvider] : [];
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                //set mssqlProviderID to ProviderID and ProviderID to BillingProviderID
                $newRow['mssqlProviderID'] = $row['ProviderID'];
                $newRow['ProviderID'] = $row['BillingProviderID'];
                $newRow['fullName'] = str_replace('  ',' ',$newRow['FirstName'] . ' ' . $newRow['MiddleName'] . ' ' . $newRow['LastName']);
                $newRow['is_updated'] = '1';
                $newRow['Search_Name'] = str_replace('  ', ' ', str_replace(',', '', $row['FirstName'] . ' ' . $row['LastName'] . ' ' . $row['FirstName']));
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                H::ExcelLog('Updating: ' . $uniqueID,'syncProviders.log');
                H::ExcelLog(print_r($newRow,true),'syncProviders.log');
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);

                if (!empty($newRow['EmploymentStatusId'])
                    && $newRow['EmploymentStatusId'] != $dbRecord[0]->EmploymentStatusID
                    && in_array($newRow['EmploymentStatusId'], [
                        MssqlEmploymentStatus::TERMINATED->value,
                        MssqlEmploymentStatus::ON_LEAVE->value,
                        MssqlEmploymentStatus::INACTIVE->value,
                    ])
                ) {
                    //event(new MssqlRecruiterTrackerOutdated($dbRecord[0]->id));
                }

            } else {
                echo 'Insert Record' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                //set mssqlProviderID to ProviderID and ProviderID to BillingProviderID
                $newRow['mssqlProviderID'] = $row['ProviderID'];
                $newRow['ProviderID'] = $row['BillingProviderID'];
                $newRow['fullName'] = str_replace('  ',' ',$newRow['FirstName'] . ' ' . $newRow['MiddleName'] . ' ' . $newRow['LastName']);
                $newRow['is_updated'] = '1';
                $newRow['Search_Name'] = str_replace('  ', ' ', str_replace(',', '', $row['FirstName'] . ' ' . $row['LastName'] . ' ' . $row['FirstName']));
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                H::ExcelLog('Inserting','syncProviders.log');
                H::ExcelLog(print_r($newRow,true),'syncProviders.log');
                DB::table($tableName)->insert($newRow);
                $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");

                $providerIds[] = $dbRecord[0]->id;
                $counter++;

                if ($counter >= $batchSize) {
                    event(new ProvidersBatchSynced($providerIds));
                    $providerIds = [];
                    $counter = 0;
                }

                if (!empty($dbRecord)) {
                    H::ExcelLog('new id: ' . $dbRecord[0]->id,'syncProviders.log');
                }else{
                    H::ExcelLog('not found','syncProviders.log');
                }
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
            //remove provider from candidate map if needed
            if($row['HiredDate']>'2025-04-01'){
                echo '     Candidates - Matching ' . "\n";
                H::ExcelLog('     Candidates - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromCandidates.log');
                $providerAddress = $row['Address1'];
                $providerAddress = addslashes(strtolower($providerAddress));
                $providerAddress = str_replace(" ","",$providerAddress);
                $lname = addslashes(strtolower($row['LastName']));
                $fname = addslashes(strtolower($row['FirstName']));
                $email = strtolower($row['External_Email']);
                $phone = preg_replace("/[^0-9]/", "", $row['Phone']);
                $mobile = preg_replace("/[^0-9]/", "", $row['Mobile']);
                $query = DB::table("candidates");
                $rawWhere = '(lcase(replace(`address`," ","")) = "' . $providerAddress . '" and lcase(`firstName`) like "%' . $fname . '%" and
                lcase(`lastName`) like "%' . $lname . '%") ';
                if(trim($email)!='') {
                    $rawWhere .= ' or lcase(email) = "' . $email . '"';
                }
                if(trim($phone)!='') {
                    $rawWhere .= ' or REGEXP_REPLACE(homePhone, "[^0-9]", "") = "' . $phone . '" ';
                }
                if(trim($mobile)!='') {
                    $rawWhere .= ' or REGEXP_REPLACE(mobile, "[^0-9]", "") = "' . $mobile . '"';
                }
                $query->whereRaw($rawWhere);
                $match = $query->select('*')->get()->toArray();
                if (count($match) > 0) {
                    foreach($match as $key=>$values){
                        echo '     Found a match.  Removing ' . htmlspecialchars($values->fullName, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->email, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                        H::ExcelLog('     Found a match.  Removing ' . $values->fullName . ' (' . $values->email . ', Phone: ' . $row['Phone'] . ', Mobile: ' . $row['Mobile'] . ', Address: ' . $row['Address1'] . ')', 'removeNursesFromCandidates.log');
                        DB::table('candidates')->where('id',$values->id)->update(['is_ignored' => 1]);
                    }
                }
            }
            if($row['HiredDate']>'2024-04-01'){
                //if the user hasn't been removed from bon maps,  search and try to remove
                if(isset($dbRecord[0]->id) && isset($dbRecord[0]->removed_from_bon_map) && $dbRecord[0]->removed_from_bon_map==0){
                    //get facility
                    echo 'Trying to remove ' . $row['LastName'] . ', ' . $row['FirstName'] . ' (' . $row['ClinicianID'] . ') from the BON Nurse maps' . "\n";
                    H::ExcelLog('Trying to remove ' . $row['LastName'] . ', ' . $row['FirstName'] . ' (' . $row['ClinicianID'] . ') from the BON Nurse maps', 'removeNursesFromBon.log');
                    $tmp = DB::select("select State from mssql_facilities where FacilityID = " . $row['FacilityID']);
                    if(!empty($tmp)) {
                        $state = $tmp[0]->State;
                        if($state=='TX'){
                            $providerAddress = $fname = $lname = '';
                            $providerName = $row['LastName'] . ', ' . $row['FirstName'];
                            echo '     Texas - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     Texas - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_tx");
                            $query->whereRaw('lcase(replace(Mailing_Address_Line_1," ","")) = "' . $providerAddress . '"');
                            $query->whereRaw('lcase(`Name`) like "%' . $fname . '%" and lcase(`Name`) like "%' . $lname . '%"');
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    echo '     Found a match.  Removing ' . htmlspecialchars($values->Name, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->License_Number, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                                    H::ExcelLog('     Found a match.  Removing ' . $values->Name . ' (' . $values->License_Number . ')', 'removeNursesFromBon.log');
                                    $uniqueID = json_encode(['License_Number' => $values->License_Number]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 30, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_tx')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='CO'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            echo '     Colorado - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     Colorado - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_co");
                            $query->whereRaw('lcase(replace(Address_Line_1," ","")) = "' . $providerAddress . '"');
                            $query->whereRaw('lcase(Formatted_Name) like "%' . $fname . '%" and lcase(Formatted_Name) like "%' . $lname . '%"');
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    $uniqueID = json_encode(['License_Number' => $values->License_Number]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 32, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_co')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='AZ'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            echo '     Arizona - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     Arizona - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_az");
                            $query->whereRaw('lcase(replace(Address," ","")) = "' . $providerAddress . '"');
                            $query->whereRaw('lcase(FirstName) = "' . $fname . '" and lcase(LastName) = "' . $lname . '"');
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    $uniqueID = json_encode(['FirstName' => $values->FirstName, 'LastName' => $values->LastName, 'Address' => $values->Address]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 31, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_co')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='NC'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            $email = addslashes(strtolower($row['External_Email']));
                            echo '     North Carolina - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     North Carolina - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_nc");

                            $rawWhere = '(lcase(replace(Address_1," ","")) = "' . $providerAddress . '" and lcase(Name) like "%' . $fname . '%" and lcase(Name) like "%' . $lname . '%") ';
                            if(trim($email)!='') $rawWhere .= ' or lcase(Email) = "' . $email . '"';
                            $query->whereRaw($rawWhere);
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    echo '     Found a match.  Removing ' . htmlspecialchars($values->Name, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->{'Certification_#'}, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                                    H::ExcelLog('     Found a match.  Removing ' . $values->Name . ' (' . $values->{'Certification_#'} . ')', 'removeNursesFromBon.log');
                                    $uniqueID = json_encode(['Certification_#' => $values->{'Certification_#'}]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 19, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_nc')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='SC'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            echo '     South Carolina - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     South Carolina - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_sc");
                            $query->whereRaw('lcase(replace(Address1," ","")) = "' . $providerAddress . '"');
                            $query->whereRaw('lcase(`Name`) like "%' . $fname . '%" and lcase(`Name`) like "%' . $lname . '%"');
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    echo '     Found a match.  Removing ' . htmlspecialchars($values->Name, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->Credential_Num, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                                    H::ExcelLog('     Found a match.  Removing ' . $values->Name . ' (' . $values->Credential_Num . ')', 'removeNursesFromBon.log');
                                    $uniqueID = json_encode(['Credential_Num' => $values->Credential_Num]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 32, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_sc')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='FL'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            $email = addslashes(strtolower($row['External_Email']));
                            $phone = preg_replace("/[^0-9]/", "", $row['Phone']);
                            $mobile = preg_replace("/[^0-9]/", "", $row['Mobile']);
                            echo '     Florida - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     Florida - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_fl");
                            $rawWhere = '(lcase(replace(`Mailing-Address-Line1`," ","")) = "' . $providerAddress . '" and lcase(`Name`) like "%' . $fname . '%" and
                            lcase(`Name`) like "%' . $lname . '%") ';
                            if(trim($email)!='') {
                                $rawWhere .= ' or lcase(Email) = "' . $email . '"';
                            }
                            if(trim($phone)!='') {
                                $rawWhere .= ' or REGEXP_REPLACE(Phone_Number, "[^0-9]", "") = "' . $phone . '" ';
                            }
                            if(trim($mobile)!='') {
                                $rawWhere .= ' or REGEXP_REPLACE(Phone_Number, "[^0-9]", "") = "' . $mobile . '"';
                            }

                            $query->whereRaw($rawWhere);
                            $match = $query->select('*')->get()->toArray();
                            H::ExcelLog(print_r($query->toSql(),true), 'removeNursesFromBon.log');
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    echo '     Found a match.  Removing ' . htmlspecialchars($values->Name, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($values->Phone_Number, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->{'License-Number'}, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                                    H::ExcelLog('     Found a match.  Removing ' . $values->Name . ' ' . $values->Phone_Number . ' (' . $values->{'License-Number'} . ')', 'removeNursesFromBon.log');
                                    $uniqueID = json_encode(['Phone_Number' => $values->Phone_Number]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 40, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_fl')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }elseif($state=='PA'){
                            $providerAddress = '';
                            $lname = addslashes(strtolower($row['LastName']));
                            $fname = addslashes(strtolower($row['FirstName']));
                            echo '     Pennsylvania - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"' . "\n";
                            H::ExcelLog('     Pennsylvania - Matching ' . $row['LastName'] . ', ' . $row['FirstName'] . ' and "' . $row['Address1'] . '"', 'removeNursesFromBon.log');
                            $query = DB::table("maps_bon_pa");
                            $query->whereRaw('lcase(replace(AddressLine1," ","")) = "' . $providerAddress . '"');
                            $query->whereRaw('lcase(Prerequisite_FullName) like "%' . $fname . '%" and lcase(Prerequisite_FullName) like "%' . $lname . '%"');
                            $match = $query->select('*')->get()->toArray();
                            if (count($match) > 0) {
                                foreach($match as $key=>$values){
                                    echo '     Found a match.  Removing ' . htmlspecialchars($values->Prerequisite_FullName, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars($values->AddressLine1, ENT_QUOTES, 'UTF-8') . ')' . "\n";
                                    H::ExcelLog('     Found a match.  Removing ' . $values->Prerequisite_FullName . ' (' . $values->AddressLine1 . ')', 'removeNursesFromBon.log');
                                    $uniqueID = json_encode(['Prerequisite_FullName' => $values->Prerequisite_FullName, 'AddressLine1' => $values->AddressLine1]);
                                    $formattedData = [];
                                    $excluded = ['needs_geocoding', 'lat', 'tmplat','tmplng','lng','lat2','lng2', 'created_at', 'updated_at', 'is_updated','is_ignored'];
                                    foreach($values as $k=>$v){
                                        if(!in_array($k,$excluded)) {
                                            $formattedData[$key][$k] = $v;
                                        }
                                    }
                                    $formattedData = json_encode($formattedData);
                                    $insertArr = ["map_id" => 22, "unique_identifier" => $uniqueID, "value" => $formattedData, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')];
                                    var_dump($insertArr);
                                    echo "\n\n\n\n";
                                    DB::table('map_ignore')->insert($insertArr);
                                    DB::table('maps_bon_pa')->where('id',$values->id)->update(['is_ignored' => 1]);
                                    DB::table('mssql_providers')->where('id',$dbRecord[0]->id)->update(['removed_from_bon_map' => 1]);
                                }
                            }
                        }else{
                            echo 'Cannot check BON due to bad facility for ' . $row['ProviderID'] . "\n";
                        }
                    }else{
                        echo 'Cannot check BON due to bad facility for ' . $row['ProviderID'] . "\n";
                    }

                }
            }
        }

        if (!empty($providerIds)) {
            event(new ProvidersBatchSynced($providerIds));
        }


        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        // No records in this batch does not mean the provider no longer exists in the source -
        // it can also mean the sync stalled or the source table was temporarily empty/incomplete.
        // Soft-deleting on that basis wrongly hid thousands of active providers (see incident 2026-09-28/29).
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

   /**
     * Sync Providers.
     */
    public function syncPayCom()
    {
        echo "Sync Paycom \n";
        $syncType = 'paycom';
        $mssqlTableName = '[Excelss].[GENERAL_PayCom]';
        $tableName = 'paycom';
        $fieldName = 'EmployeeRegisterID';
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
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            H::ExcelLog(print_r($row,true),'syncPaycom.log');
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row[$fieldName];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                H::ExcelLog('Updating: ' . $uniqueID,'syncPaycom.log');
                $newRow['is_updated'] = '1';
                H::ExcelLog(print_r($newRow,true),'syncPaycom.log');
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                H::ExcelLog('Inserting','syncPaycom.log');
                H::ExcelLog(print_r($newRow,true),'syncPaycom.log');
                DB::table($tableName)->insert($newRow);
                $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
                if (!empty($dbRecord)) {
                    H::ExcelLog('new id: ' . $dbRecord[0]->id,'syncPaycom.log');
                }else{
                    H::ExcelLog('not found','syncPaycom.log');
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
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }


    public function matchToTrackers(){
        //now see if we can match any tracker records with missing aoc_provider_id and ClinicianID
        $rawData = DB::select("SELECT rt.Candidate, rt.Date_Contacted, rt.id, Clinician_ID, c.email, c.homePhone, c.mobile, c.candidate_type, c.id as candidate_id,
                    c.recruiterPhoneScreenDate, c.clinicalInterviewDate, c.offerExtendedDate
                    FROM `mssql_recruitertracker` rt
                    left join candidates c on rt.aoc_candidate_id = c.id
                    WHERE rt.Date_Contacted > '2023-11-01' and rt.is_active = 1 and rt.Candidate_Current_Status <> 'Inactive' and ifnull(rt.aoc_provider_id,0) = 0  and ifnull(rt.aoc_candidate_id,0) <> 0");

        //start processing records
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            var_dump($row);
            echo "\n";
            $trackerId = $row['id'];
            H::ExcelLog('checking tracker ' . $trackerId . ' for ' . $row['Candidate'] . ' (' . $row['Clinician_ID'] . ')', 'clinicianMatch.log');
            $clinicianId = $row['Clinician_ID'];
            $email = strtolower($row['email']);
            $homePhone = preg_replace("/[^0-9]/", "", $row['homePhone']);
            $mobilePhone = preg_replace("/[^0-9]/", "", $row['mobile']);

            $rawWhere = '';
            if(trim($email)!=''){
                $rawWhere .= ' lcase(External_Email) = "' . $email . '"';
            }
            if(trim($homePhone)!=''){
                if($rawWhere!='') {
                    $rawWhere .= ' or ';
                }
                $rawWhere .= ' REGEXP_REPLACE(Phone, "[^0-9]", "") = "' . $homePhone . '" ';
                $rawWhere .= ' or REGEXP_REPLACE(Mobile, "[^0-9]", "") = "' . $homePhone . '" ';
            }
            if(trim($mobilePhone)!=''){
                if($rawWhere!='') {
                    $rawWhere .= ' or ';
                }
                $rawWhere .= ' REGEXP_REPLACE(Mobile, "[^0-9]", "") = "' . $mobilePhone . '"';
                $rawWhere .= ' or REGEXP_REPLACE(Phone, "[^0-9]", "") = "' . $mobilePhone . '"';
            }
            H::ExcelLog($rawWhere, 'clinicianMatch.log');
            //look for a provider with matching info
            try{
                $dbRecord = DB::table('mssql_providers')->select('id','fullName','FirstName','LastName','External_Email','Phone','Mobile','ClinicianID')
                    ->whereRaw($rawWhere)
                    ->first();
                var_dump($dbRecord);
                echo "\n";
                if (!empty($dbRecord)) {
                    echo 'found!' . "\n";
                    $aoc_provider_id = $dbRecord->id;
                    $updateRT = [];
                    $updateRT['aoc_provider_id'] = $aoc_provider_id;
                    DB::table('mssql_recruitertracker')->where('id', $trackerId)->limit(1)->update($updateRT);

                    $this->generateTherapyPdfsForProvider($row, $aoc_provider_id, $trackerId);

                    //add a note and send a notification
                    $match = '';
                    if(isset($dbRecord->External_Email) && $dbRecord->External_Email!='' && strtolower($dbRecord->External_Email)==$email) {
                        $match = ' with the email ' . $email;
                    }
                    if(isset($dbRecord->Phone) && $dbRecord->Phone!='' && preg_replace("/[^0-9]/", "", $dbRecord->Phone)==$homePhone) {
                        $match = ' with the phone number ' . $homePhone;
                    }
                    if(isset($dbRecord->Mobile) && $dbRecord->Mobile!='' && preg_replace("/[^0-9]/", "", $dbRecord->Mobile)==$mobilePhone) {
                        $match = ' with the phone number ' . $mobilePhone;
                    }
                    $notificationText = 'Candidate ' . $row['Candidate'] . ' (' . $row['Date_Contacted'] . ') was matched to clinician ' . $dbRecord->FirstName . ' ' . $dbRecord->LastName . ' (' . $dbRecord->ClinicianID . ') ' . $match;
                    Notifications::addNotification($data = ['user_id' => 15, 'title' => $notificationText, 'type' => 'tracker', 'related_item' => $trackerId]);
                    Notifications::addNotification($data = ['user_id' => 1, 'title' => $notificationText, 'type' => 'tracker', 'related_item' => $trackerId]);
                    echo htmlspecialchars($notificationText, ENT_QUOTES, 'UTF-8') . ' notification sent to admin' . "\n";
                    H::ExcelLog($notificationText, 'clinicianMatch.log');
                    $insert = Notes::insert(['entity' => 'tracker-activity', 'entity_id' => $trackerId,
                    'notes' => $notificationText, 'created_at' => date('Y-m-d H:i:s'),'updated_at' => date('Y-m-d H:i:s')]);

                    if (isset($row['candidate_type']) && $row['candidate_type'] == 'prn') {
                        $address = [];
                        $candidate = Candidates::find($row['candidate_id']);
                        if(isset($candidate['address']) && !empty($candidate['address'])) {
                            $address['address1'] = $candidate['address'];
                        }
                        if(isset($candidate['address2']) && !empty($candidate['address2'])) {
                            $address['address2'] = $candidate['address2'];
                        }
                        if(isset($candidate['city']) && !empty($candidate['city'])) {
                            $address['city'] = $candidate['city'];
                        }
                        if(isset($candidate['state']) && !empty($candidate['state'])) {
                            $address['state'] = $candidate['state'];
                        }
                        if(isset($candidate['postalCode']) && !empty($candidate['postalCode'])) {
                            $address['zipcode'] = $candidate['postalCode'];
                        }
                        if (!empty($address)) {
                            $coordinatorId = H::getHelper('ConstantHelper')->getCoverageCoordinatorByPolygon($address, $aoc_provider_id);
                            if($coordinatorId) {
                                DB::table('mssql_providers')->where('id', $aoc_provider_id)->update(['cc_id' => $coordinatorId]);
                            }
                        }
                    }

                } else {
                    echo 'notfound' . "\n";
                    $notificationText = 'Candidate ' . $row['Candidate'] . ' (' . $row['Date_Contacted'] . ') was NOT matched to clinician';
                    H::ExcelLog($notificationText, 'clinicianMatch.log');
                }
            }catch (\Exception $e){
                H::ExcelLog('Error: ' . $e->getMessage(), 'clinicianMatchError.log');
            }
        }
    }

    private function generateTherapyPdfsForProvider(array $row, int $providerId, int $trackerId): void
    {
        $stepsToGenerate = [
            'recruiter_phone_screen' => [
                'date_field' => 'recruiterPhoneScreenDate',
                'document_type' => 'therapy_recruiter_phone_screen',
                'description' => 'Therapy Recruiter Phone Screen',
            ],
            'clinical_interview' => [
                'date_field' => 'clinicalInterviewDate',
                'document_type' => 'therapy_clinical_interview',
                'description' => 'Therapy Clinical Interview',
            ],
            'offer_extended' => [
                'date_field' => 'offerExtendedDate',
                'document_type' => 'therapy_offer_extended',
                'description' => 'Therapy Offer Extended',
            ],
        ];

        $pdfService = app(TherapyPdfService::class);

        foreach ($stepsToGenerate as $step => $config) {
            if (empty($row[$config['date_field']])) {
                continue;
            }

            try {
                $filename = $pdfService->store((int) $row['candidate_id'], $step);

                DB::table('aoc_documents')->insert([
                    'entity_type' => 'provider',
                    'entity_id' => $providerId,
                    'tracker_id' => $trackerId,
                    'document_type' => $config['document_type'],
                    'document_filename' => $filename,
                    'description' => $config['description'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                H::ExcelLog("Generated {$step} PDF for candidate {$row['candidate_id']} → provider {$providerId}", 'clinicianMatch.log');
            } catch (\Throwable $e) {
                H::ExcelLog("Failed to generate {$step} PDF for candidate {$row['candidate_id']}: {$e->getMessage()}", 'clinicianMatchError.log');
            }
        }
    }
}
