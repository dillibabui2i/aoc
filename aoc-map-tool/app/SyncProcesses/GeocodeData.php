<?php

namespace App\SyncProcesses;

use App\Helpers\Geocoding;
use App\Helpers\H;
use App\Http\Controllers\MapAbstractController;
use  App\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class GeocodeData
{
    public function getGeocodingStatus()
    {
        $allMapList = DB::table('map_configurations')->select('map_tablename', 'latlong_fields')->get();
        if (count($allMapList) > 0) {
            $totalCount = 0;
            foreach ($allMapList as $allMapData) {
                if (Schema::hasTable($allMapData->map_tablename) && Schema::hasColumn($allMapData->map_tablename, 'needs_geocoding')) {
                    $tmp = DB::select('select count(*) as record_count from ' . $allMapData->map_tablename . " where needs_geocoding='1'");
                    echo htmlspecialchars($allMapData->map_tablename, ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars($tmp[0]->record_count, ENT_QUOTES, 'UTF-8') . "\n";
                    $totalCount = $totalCount + $tmp[0]->record_count;
                }
            }
            echo 'Total Count: ' . $totalCount . "\n";
        }
        die();
    }

    public function testLocation($address)
    {
        $test = '2222 Parkside Drive Denton, TX';
        $latlong = Geocoding::getGeoTomTom($address);
        echo $latlong['lat'] . ',' . $latlong['lng'];
        echo "\n";
    }

    /**
     * Start routine that processes geocoding in the background.
     */
    public function ProcessLatLng()
    {
        echo "Process Lat Lng  \n";

        //start batch
        $batch_status = H::getHelper('Sync')->startBatch('geocoding');
        H::ExcelLog('starting geo - ' . H::getHelper('Sync')->getSessionId(), 'geobatch.log');
        echo $batch_status . "\n";
       if ($batch_status == -1) {
            echo 'stop process';
            H::ExcelLog('------ skipping batch', 'geobatch.log');
            exit;
        }
        H::ExcelLog('starting geo - ' . H::getHelper('Sync')->getSessionId(), 'geobatch.log');
        $batch_info = H::getHelper('Sync')->get_batch_information('geocoding');
        $batch_result = 0;
        var_dump($batch_info);
        echo "\n";

        $limit = $batch_info['batch_size'];
        echo $forceMap = $batch_info['last_updated_id'];
        $content = 'Process Lat Lng START ON ' . date('Y-m-d H:i:s') . " \n";
        if ($forceMap == '') {
            echo 'process first available file' . "\n";
            $allMapList = DB::table('map_configurations')->select('map_tablename', 'latlong_fields', 'latlong_fields2')->get();
        } else {
            echo 'process custom table: ' . $batch_info['last_updated_id'] . "\n";
            $allMapList = DB::table('map_configurations')->select('map_tablename', 'latlong_fields', 'latlong_fields2')->where('map_tablename', $forceMap)->get();
        }

        $processedMap = 0;

        if (count($allMapList) > 0) {
            foreach ($allMapList as $allMapData) {

                $locationAddress = [];
                $locationAddress2 = [];
                $saveAddress = 0;
                echo 'processing: ' . htmlspecialchars($allMapData->map_tablename, ENT_QUOTES, 'UTF-8') . "\n";
                H::ExcelLog('--- ' . $allMapData->map_tablename, 'geobatch.log');
                if (Schema::hasTable($allMapData->map_tablename) && Schema::hasColumn($allMapData->map_tablename, 'needs_geocoding') && $allMapData->map_tablename!='raw_coverage_needs' && $allMapData->map_tablename!='raw_therapy_map') {
                    echo  $limit . "\n";
                    $latlongFields = json_decode($allMapData->latlong_fields);
                    if (empty($latlongFields)) {
                        H::ExcelLog('--- ' . $allMapData->map_tablename . ' does not have addresses mapped', 'geobatch.log');
                        echo htmlspecialchars('--- ' . $allMapData->map_tablename . ' does not have addresses mapped', ENT_QUOTES, 'UTF-8') . "\n";
						continue;
                    }else{
                        //now make sure all mapped fields exist
                        foreach ($latlongFields as $lk => $lv) {
                            if (!Schema::hasColumn($allMapData->map_tablename, $lv)) {
                                H::ExcelLog('--- Field in configuration table does not exist in ' . $allMapData->map_tablename, 'geobatch.log');
                                echo htmlspecialchars('--- Field in configuration table does not exist in ' . $allMapData->map_tablename, ENT_QUOTES, 'UTF-8') . "\n";
                                $batch_status = H::getHelper('Sync')->stopBatch('geocoding');
                                exit;
                            }
                        }

                        $latlongFields2 = json_decode($allMapData->latlong_fields2);
                        if (empty($latlongFields2)) {
                            H::ExcelLog('--- ' . $allMapData->map_tablename . ' does not have addresses2 mapped', 'geobatch.log');
                            echo htmlspecialchars('--- ' . $allMapData->map_tablename . ' does not have addresses2 mapped', ENT_QUOTES, 'UTF-8') . "\n";
                        }
                        //now make sure all mapped fields exist
                        if(!empty($latlongFields2)) {
                            foreach ($latlongFields2 as $lk => $lv) {
                                if (!Schema::hasColumn($allMapData->map_tablename, $lv)) {
                                    H::ExcelLog('---Address2 Field in configuration table does not exist in ' . $allMapData->map_tablename, 'geobatch.log');
                                    echo htmlspecialchars('--- Address2 Field in configuration table does not exist in ' . $allMapData->map_tablename, ENT_QUOTES, 'UTF-8') . "\n";
                                    $batch_status = H::getHelper('Sync')->stopBatch('geocoding');
                                    exit;
                                }
                            }
                        }



                        $mapNeeedGeoList = DB::table($allMapData->map_tablename)->select('*')->where('needs_geocoding', 1)->limit($limit)->get();
                        echo 'count: ' . count($mapNeeedGeoList) . "\n";
                        if ($forceMap != '' && count($mapNeeedGeoList) == 0) {
                            H::getHelper('Sync')->update_last_updated('geocoding', '', '');
                        }

                        $mapNeeedGeoList = json_decode(json_encode($mapNeeedGeoList), true);

                        if (count($mapNeeedGeoList) > 0) {
                            echo 'count: ' . count($mapNeeedGeoList) . "\n";
                            $processedMap = 1;
                            foreach ($mapNeeedGeoList as $mapNeeedGeoData) {
                                echo '--- processing ' . "\n";
                                $content = $content . 'Process Table ' . $allMapData->map_tablename . " \n";
                                foreach ($latlongFields as $lk => $lv) {
                                    $locationAddress[$lk] = $mapNeeedGeoData[$lv] ?? '';
                                }
                                $locationAddress['country'] = 'USA';
                                $tempZipcode = $locationAddress['zipcode'] ?? '';
                                unset($locationAddress['zipcode']);
                                unset($locationAddress['County']);
                                $locationAddress['zipcode'] = $tempZipcode;
                                $saveAddress = 0;
                                echo ($locationAddress['address1'] ?? '') . "\n";

                                $latlong = [];
                                if($allMapData->map_tablename != 'maps_nth') {
                                    $latlong = Geocoding::getLatLngForAddress2($locationAddress);
                                }else {
                                    echo 'Map table is maps_nth so skipped the data - ' . print_r($locationAddress, true). "\n";
                                    H::ExcelLog('Map table is maps_nth so skipped the data - ' . print_r($locationAddress, true), 'geobatch.log');
                                }

                                if (empty($latlong)) {
                                    $saveAddress = 1;
                                    echo '    looking in api' . "\n";
                                    $useGoogleApiMapArray = ['maps_nth','maps_clients','maps_coverageneeds','maps_therapy','maps_branch','maps_covclin', 'map79'];
                                    if(in_array($allMapData->map_tablename,$useGoogleApiMapArray)) {
                                        $latlong = Geocoding::getGeoGoogle(implode(',', $locationAddress));
                                    }else {
                                        $latlong = Geocoding::getGeoTomTom(implode(',', $locationAddress));
                                    }
                                } else {
                                    echo '    found in location table' . "\n";
                                    var_dump($latlong);
                                    echo "\n";
                                }

                                $latlong2='';
                                $saveAddress2=0;
                                if (!empty($latlong)) {
                                    $content = $content . 'Adding Lat=' . $latlong['lat'] . ' And Lng=' . $latlong['lng'] . ' For Record Id =' . $mapNeeedGeoData['id'] . ' For Table ' . $allMapData->map_tablename . " \n";
                                    $currentMapTableUpdateArr = ['needs_geocoding' => 0,'lat'=> $latlong['lat'], 'lng'=> $latlong['lng']];
                                    DB::table($allMapData->map_tablename)->where('id', $mapNeeedGeoData['id'])->update($currentMapTableUpdateArr);
                                    echo '    saved to map table' . "\n";
                                    //save address in location db
                                    if ($saveAddress == 1) {
                                        echo '    saving to location table' . "\n";
                                        $locationAddress['lat'] = $latlong['lat'];
                                        $locationAddress['lng'] = $latlong['lng'];
                                        Geocoding::saveAddressData($locationAddress);
                                    }
                                } else {
                                    $currentMapTableUpdateArr = ['needs_geocoding' => 0];
                                    DB::table($allMapData->map_tablename)->where('id', $mapNeeedGeoData['id'])->update($currentMapTableUpdateArr);
                                }


                                echo '--- saved' . "\n";
                            }
                        }
                    }
                    if ($processedMap == 1) {
                        break;
                    }
                }
            }
        }
        $content = $content . 'Process Lat Lng ENDs ON ' . date('Y-m-d H:i:s') . " \n";
        $fileName = 'processlatlng-' . date('Y-m-d') . '.txt';
        Storage::put($fileName, $content);
        $batch_status = H::getHelper('Sync')->stopBatch('geocoding');
        H::ExcelLog('stop geo - ' . H::getHelper('Sync')->getSessionId(), 'geobatch.log');
        echo "Process Lat Lng finished  \n";
        exit;
    }

    //temporary function to clear duplicate data
    public function cleanData()
    {
        $syncType = 'cleandata';
        $tablename = 'location';
        $fieldname = 'location_search';
        echo 'Clean Data';
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
        $data = DB::table($tablename)->select('*')->where('id', '>', $last_updated_id)->orderBy('id')->limit($limit)->get();
        foreach ($data as $row) {
            $last_updated_id = $row->id;
            $match = $row->$fieldname;
            echo htmlspecialchars($last_updated_id, ENT_QUOTES, 'UTF-8') . "\n";
            $qry = 'select * from ' . $tablename . ' where ' . $fieldname . " = '" . $match . "' and id < " . $last_updated_id;
            echo htmlspecialchars($qry, ENT_QUOTES, 'UTF-8') . "\n";
            $duplicate = DB::select($qry);
            if (!empty($duplicate)) {
                echo 'This is a duplicate - deleting it' . "\n";
                H::ExcelLog('deleting: ' . $last_updated_id . ' (' . $match . ')', 'clean-' . $tablename . '.log');
                $deleted = DB::table($tablename)->where('id', $last_updated_id)->delete();
                var_dump($deleted);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
