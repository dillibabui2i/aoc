<?php

namespace App\SyncProcesses;

use App\Helpers\Geocoding;
use App\Helpers\H;
use  App\Http\Controllers\MapAbstractController;
use App\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class TransformTherapy
{

    /**
     * start routine to sync data from mssql for the therapy ,ap
     */

    public function TransformTherapy()
    {
        echo "Sync Therapy \n";
        $syncType = 'therapy';
        $tableName = 'maps_therapy';
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
        $tsql = 'SELECT top ' . $limit . " * FROM Excelss.MAP_TextBlasting 
			WHERE [Clinician ID]  > '$last_updated_id'
			ORDER BY [Clinician ID] ASC";
        $tsql = "SELECT top " . $limit . " *
                FROM [Excelss].[TherapyMap] 
                where CGTaskID > " . $last_updated_id . " order by CGTaskID";
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
            $last_updated_id = $row['CGTaskID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['CGTaskID'];
            echo $uniqueID . "\n";
            $query = "select * from maps_therapy where `CGTaskID` = ?";
            echo $query . "\n";
            $dbClient = DB::select($query,[$uniqueID]);
            var_dump($dbClient);
            echo "\n";
            if (!empty($dbClient)) {
                echo 'Update Clinician' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                $newRow['Client_Age'] = Carbon::now()->diffInYears(Carbon::parse($row['Client_DOB']));
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['needs_geocoding'] = 1;
                DB::table('maps_therapy')->whereRaw('`CGTaskID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Clinician' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['needs_geocoding'] = 1;
                DB::table('maps_therapy')->insert($newRow);
            }
        }
        H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
        if ($recordCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
            $deleted = DB::table($tableName)->where('is_updated', 0)->delete();
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

     public function TransformTherapyold()
    {
        echo "Transform Therapy Data  \n";
        $syncType = 'rawTherapy';
        $tableName = 'maps_therapy';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        H::ExcelLog('starting transform therapy - ' . H::getHelper('Sync')->getSessionId(), 'rawTherapy.log');
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'rawTherapy.log');
            exit;
        }
        H::ExcelLog('starting transform therapy - ' . H::getHelper('Sync')->getSessionId(), 'rawTherapy.log');
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $limit = $batch_info['batch_size'];
        $last_updated_id = $batch_info['last_updated_id'];
        $rawData = DB::table('raw_therapy_map')->select('*')->where('processed', 0)->limit($limit)->get();
        $batchCount = count($rawData);
        if ($last_updated_id == 'donetillnextupload') {
            if ($batchCount > 0) {
                //start a new batch
                $updated = DB::table('maps_therapy')->update(['is_updated' => 0]);
                H::getHelper('Sync')->update_last_updated($syncType, '', '');
            } else {
                echo 'Skipping because we are done till the next batch is uploaded';
                $batch_status = H::getHelper('Sync')->stopBatch($syncType);
                exit;
            }
        }
        //if we haven't set 'donetillnextupload' yet and no records were found,   delete all rows with 'is_updated = 0' and stop from processing until a new file is uploaded
        if ($batchCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, '', 'donetillnextupload');
            $deleted = DB::table($tableName)->where('is_updated', 0)->delete();
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        //prepare the location fields for geocoding
        $mapConfig = DB::table('map_configurations')->select('map_tablename', 'latlong_fields')->where('map_tablename', 'maps_therapy')->first();

        $latlongFields = json_decode($mapConfig->latlong_fields);
        if (empty($latlongFields)) {
            echo 'lat/lng mapping fields is empty in map config - cannot proceed';
            exit;
        }

        $locationAddress = [];

        //start processing records
        $recordCount = 0;
        $updatedCount = 0;
        $insertedCount = 0;
        $duplicateCount = 0;
        $rawData = json_decode(json_encode($rawData), true);
        foreach ($rawData as $row) {
            $saveAddress = 0;
            $recordCount++;
            $rawID = $row['id'];
            $tmpClientName = $row['Client_First_Name'] . ' ' . $row['Client_Last_Name'];
            $tmpClinicianName = $row['Primary_Clinician_First_Name'] . ' ' . $row['Primary_Clinician_Last_Name'];
            echo $tmpClientName . ' - ' . $tmpClinicianName . "\n";
            $last_updated_date = '';
            $last_updated_id = $rawID;
            //prepare record for maps_therapy
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                if ($key != 'id' && $key != 'Client_First_Name' && $key != 'Client_Last_Name' && $key != 'Primary_Clinician_First_Name' && $key != 'Primary_Clinician_Last_Name' && $key != 'processed') {
                    $newRow[$key] = $value;
                }
            }
            $newRow['Client_Name'] = $tmpClientName;
            $newRow['Primary_Clinician_Name'] = $tmpClinicianName;
            $newRow['is_updated'] = '1';
            $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');

            //first do the geocode so we can add lat/lng to insert query
            foreach ($latlongFields as $lk => $lv) {
                $locationAddress[$lk] = $newRow[$lv] ?? '';
            }

            $locationAddress['country'] = 'USA';
            $tempZipcode = $locationAddress['zipcode'];
            unset($locationAddress['zipcode']);
            unset($locationAddress['County']);
            $locationAddress['zipcode'] = $tempZipcode;
            echo 'address:' . "\n";
            var_dump($locationAddress);
            echo "\n";
            $latlong = Geocoding::getLatLngForAddress($locationAddress);
            var_dump($latlong);
            echo "\n";
            if (empty($latlong)) {
                $saveAddress = 1;
                echo '    looking in api' . "\n";
                H::ExcelLog('search in api for ' . implode(',', $locationAddress), 'geocodingapi.log');
                $latlong = Geocoding::getGeoTomTom(implode(',', $locationAddress));
            } else {
                $saveAddress = 0;
                echo '    found in location table' . "\n";
            }
            if (!empty($latlong)) {
                $newRow['needs_geocoding'] = 0;
                $newRow['lat'] = $latlong['lat'];
                $newRow['lng'] = $latlong['lng'];
                //save address in location db
                if ($saveAddress == 1) {
                    echo '    saving to location table' . "\n";
                    $locationAddress['lat'] = $latlong['lat'];
                    $locationAddress['lng'] = $latlong['lng'];
                    Geocoding::saveAddressData($locationAddress);
                }
            } else {
                $newRow['needs_geocoding'] = 0;
                $newRow['lat'] = '';
                $newRow['lng'] = '';
            }
            //first see if this record is already in the maps_therapy table
            $dbRecord = DB::table('maps_therapy')
                            ->where('Client_Name', '=', $tmpClientName)
                            ->where('Primary_Clinician_Name', '=', $tmpClinicianName)
                            ->first();

            if (!empty($dbRecord)) {
                if ($dbRecord->is_updated == 1) {
                    $duplicateCount++;
                    echo 'Duplicate Record' . "\n";
                } else {
                    echo 'Update Record' . "\n";
                    $uniqueID = $dbRecord->id;
                    echo 'id: ' . htmlspecialchars($uniqueID, ENT_QUOTES, 'UTF-8') . "\n";
                    $tmp = DB::table($tableName)->whereRaw('`id`= ?', [$uniqueID])->update($newRow);
                    $updatedCount++;
                }
            } else {
                echo 'Insert Record' . "\n";
                //insert new record into maps_therapy
                $tmp = DB::table($tableName)->insert($newRow);
                echo "\n";
                $insertedCount++;
            }
            DB::table('raw_therapy_map')->whereRaw('`id`= ?', [$rawID])->update(['processed' => 1]);
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'rawTherapy.log');
        echo 'updated: ' . $updatedCount . "\n";
        echo 'inserted: ' . $insertedCount . "\n";
        echo 'duplicates: ' . $duplicateCount . "\n";
        echo 'processed: ' . $recordCount . "\n";
    }
}
