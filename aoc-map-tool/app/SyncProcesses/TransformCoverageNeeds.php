<?php

namespace App\SyncProcesses;

use App\Events\Map\MapUpdatedEvent;
use App\Helpers\Geocoding;
use App\Helpers\H;
use  App\Http\Controllers\MapAbstractController;
use App\Location;
use Illuminate\Support\Facades\DB;

class TransformCoverageNeeds
{
    private bool $mapTouched = false;
    /**
     * Start routine that transforms uploaded coverage needs data
     * raw data is uploaded to raw_coverage_needs table
     * we will update it to maps_coverageneeds.
     */
    public function TransformCoverageNeeds()
    {
        echo "Transform Coverage Needs Data  \n";
        $syncType = 'rawCoverageNeeds';
        $tableName = 'maps_coverageneeds';
        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        H::ExcelLog('check batch for  transform coverage needs - ' . H::getHelper('Sync')->getSessionId(), 'coverageneeds.log');
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'coverageneeds.log');
            exit;
        }
        H::ExcelLog('starting transform coverage needs - ' . H::getHelper('Sync')->getSessionId(), 'coverageneeds.log');
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $limit = $batch_info['batch_size'];
        $last_updated_id = $batch_info['last_updated_id'];
        $rawData = DB::table('raw_coverage_needs')->select('*')->where('processed', 0)->limit($limit)->get();
        $batchCount = count($rawData);
        if ($last_updated_id == 'donetillnextupload') {
            if ($batchCount > 0) {
                //start a new batch
                H::ExcelLog('Set all is_updated = 0 and start a new batch', 'coverageneeds.log');
                $updated = DB::table('maps_coverageneeds')->update(['is_updated' => 0]);
                $this->mapTouched = true;
                H::getHelper('Sync')->update_last_updated($syncType, '', '');
            } else {
                H::ExcelLog('Skipping because we are done till the next batch is uploaded', 'coverageneeds.log');
                echo 'Skipping because we are done till the next batch is uploaded';
                $batch_status = H::getHelper('Sync')->stopBatch($syncType);
                exit;
            }
        }
        //if we haven't set 'donetillnextupload' yet and no records were found,   delete all rows with 'is_updated = 0' and stop from processing until a new file is uploaded
        if ($batchCount == 0) {
            H::getHelper('Sync')->update_last_updated($syncType, '', 'donetillnextupload');
            H::ExcelLog('Finished with batch - delete records where is_updated still = 0 and last_activity is null', 'coverageneeds.log');
            $deleted = DB::table($tableName)->where('is_updated', 0)->whereNull('last_activity')->delete();
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
            exit;
        }
        //prepare the location fields for geocoding
        $mapConfig = DB::table('map_configurations')->select('map_tablename', 'latlong_fields')->where('map_tablename', 'maps_coverageneeds')->first();

        $latlongFields = json_decode($mapConfig->latlong_fields);
        if (empty($latlongFields)) {
            echo 'lat/lng mapping fields is empty in map config - cannot proceed';
            H::getHelper('Sync')->stopBatch($syncType);
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
            $newRow = [];
            $saveAddress = 0;
            $recordCount++;
            $rawID = $row['id'];
            $tmpDate = $row['Date'];
            $filterDate = date('m/d/Y', strtotime($tmpDate));
            echo '*****   ' . $filterDate . "\n";
            $tmpPatient = trim($row['Patient_ID']);
            $tmpSchedule = $row['Schedule_Time'];
            $tmpName = $row['Client_First_Name'] . ' ' . $row['Client_Last_Name'];
            echo $tmpName . ' - ' . $tmpDate . ' ' . $tmpSchedule . "\n";
            $last_updated_date = '';
            $last_updated_id = $rawID;
            //prepare record for maps_coverageneeds
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                if ($key != 'id' && $key != 'SOC' && $key != 'Episode' && $key != 'processed') {
                    $newRow[$key] = $value;
                }
            }
            $newRow['Name'] = $row['Client_First_Name'] . ' ' . $row['Client_Last_Name'];
            $newRow['State'] = substr($row['Locations'], 0, 2);
            $newRow['filterDate'] = $filterDate;
            $newRow['is_updated'] = '1';
            $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            $newRow['shift_date'] = date('Y-m-d',strtotime($row['Date']));
            $newRow['Patient_ID'] = $tmpPatient;
            $newRow['aoc_patient_id'] = 0;
            $newRow['CF_Weight'] = '';
            $newRow['Male_Nurse_Accepted'] = '';
            $newRow['Pets_in_Home'] = '';
            $newRow['Non_Smoking'] = '';
            $newRow['Covid_Vaccine'] = '';
            $newRow['Lift_Available'] = '';
            $newRow['Attends_School'] = '';

            $dbUser = [];
            if ($tmpPatient !== '') {
                $dbUser = DB::select(
                    "select * from mssql_patients where `PatientAccount` = ? and IsLatestAdmit = 1",
                    [$tmpPatient]
                );
            }
            if (count($dbUser) > 0) {
                //aoc_patient_id
                $newRow['aoc_patient_id'] = $dbUser[0]->id;
                //terminal payer
                $newRow['Payer'] = $dbUser[0]->Payer;
                //patient weight
                //0-20, 20-40, 40-60, 60+
                $tmp = $dbUser[0]->CF_Weight;
                $res = preg_replace("/[^0-9]/", "", $tmp );
                $w = floatval($res);
                if($w<20){
                    $tmp = '0-20';
                }elseif($w<40){
                    $tmp = '20-40';
                }elseif($w<60){
                    $tmp = '40-60';
                }else{
                    $tmp = '60+';
                }
                $newRow['CF_Weight'] = $tmp;
                $prefs = $dbUser[0]->PreferenceList;
                $tmp = 'No';
                if(strpos($prefs,'Male Nurse Accepted')!==false) {
                    $tmp = 'Yes';
                }
                $newRow['Male_Nurse_Accepted'] = $tmp;
                $newRow['Pets_in_Home'] = $dbUser[0]->PetsinHome;
                $tmp = 'No';
                if(strpos($prefs,'Client prefers non-smoking nur')!==false) {
                    $tmp = 'Yes';
                }
                $newRow['Non_Smoking'] = $tmp;

                $tmp = 'No';
                if(strpos($prefs,"Nurses must be COVID-19 vaccin")!==false) {
                    $tmp = 'Yes';
                }
                $newRow['Covid_Vaccine'] = $tmp;

                $tmp = $dbUser[0]->CF_LiftingRequired;
                $clearstring=str_replace('  ',' - ',filter_var($tmp, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH));
                $newRow['Lift_Available'] = $clearstring;

                $tmp = 'No';
                if(strpos($prefs,"Nurse Attending Child")!==false) {
                    $tmp = 'Yes';
                }
                $newRow['Attends_School'] = $tmp;
            }

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
                $newRow['needs_geocoding'] = 1;
                $newRow['lat'] = '';
                $newRow['lng'] = '';
            }
            var_dump($newRow);
            echo "\n\n\n";
            //first see if this record is already in the maps_coverageneeds table
            $matchCondition = [
                'Date' => $tmpDate,
                'Name' => $tmpName,
                'Schedule_Time' => $tmpSchedule,
            ];
            if ($tmpPatient !== '') {
                $matchCondition['Patient_ID'] = $tmpPatient;
            }

            $dbRecord = DB::table('maps_coverageneeds')
                            ->where($matchCondition)
                            ->first();

            if (!empty($dbRecord)) {
                if ($dbRecord->is_updated == 1) {
                    $duplicateCount++;
                    echo 'Duplicate Record' . "\n";
                    H::ExcelLog($newRow['Name'] . ' (' . $newRow['Discipline'] . ')  ------ skip duplicate', 'coverageneeds.log');
                } else {
                    echo 'Update Record' . "\n";
                    H::ExcelLog($newRow['Name'] . ' (' . $newRow['Discipline'] . ')  ------ update ', 'coverageneeds.log');
                    $uniqueID = $dbRecord->id;
                    echo 'id: ' . htmlspecialchars($uniqueID, ENT_QUOTES, 'UTF-8') . "\n";
                    DB::table($tableName)->where('id', $uniqueID)->update($newRow);
                    $this->mapTouched = true;
                    $updatedCount++;
                }
            } else {
                H::ExcelLog($newRow['Name'] . ' (' . $newRow['Discipline'] . ')  ------ insert', 'coverageneeds.log');

                echo 'Insert Record' . "\n";
                //insert new record into maps_coverageneeds
                DB::table($tableName)->insert($newRow);
                $this->mapTouched = true;
                echo "\n";
                $insertedCount++;
            }
            $updateArray = ['processed' => 1];
            DB::table('raw_coverage_needs')->whereRaw('`id`= ?', [$rawID])->update($updateArray);
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        H::ExcelLog('stop transform - ' . H::getHelper('Sync')->getSessionId(), 'coverageneeds.log');

        if ($this->mapTouched) {
            event(new MapUpdatedEvent($tableName));
        }
        echo 'updated: ' . $updatedCount . "\n";
        echo 'inserted: ' . $insertedCount . "\n";
        echo 'duplicates: ' . $duplicateCount . "\n";
        echo 'processed: ' . $recordCount . "\n";
    }
}
