<?php

namespace App\SyncProcesses;


use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Storage;

class CleanTimeData
{
    
    
    /**
     * The function to update wrong time format with blank
     *
     * @author Nayan - 13 Aug 2022
     * 
     */
    public function cleanTimeData()
    {
        $syncType = 'cleantimedata';
        $tablename = 'mssql_recruitertracker';
        echo "\n";echo 'Clean Time Data';echo "\n";
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
        $data = DB::table($tablename)->select('*')->orderBy('id')->offset($last_updated_id)->limit($limit)->get();
        $data = json_decode(json_encode($data), true);
        //time data fields
        $timeFieldArr = ['PEPI_Time', 'IPEA_Time', 'WEPI_Time', 'Candidate_Start_Time'];
        foreach ($data as $row) {
            $updateArry = [];
            var_dump($row);
            echo "\n";
            foreach ($timeFieldArr as $timeFieldData) {
                var_dump($timeFieldData);
                echo "\n";
                $last_updated_id = $row['id'];
                $pepiTime = $row[$timeFieldData];
                if (!empty($pepiTime)) {
                    echo "\n";
                    echo "====" . $pepiTime;
                    echo "\n";
                    //if string length is 2 or less than 2.
                    if (strlen(trim($pepiTime)) <= 2) {
                        $updateArry[$timeFieldData] = '';
                    } else {
                        try {
                            $dt = new \DateTime($pepiTime);
                            echo $dt->format('h:i A');
                            echo "\n";
                        } catch (\Exception $ex) {
                            $updateArry[$timeFieldData] = '';
                        }
                    }
                }
            }
            if (count($updateArry) > 0) {
                echo "Update Array";
                echo "\n";
                DB::table($tablename)->where('id', $last_updated_id)->update($updateArry);
            }
            echo "\n";
            echo "last_updated_id====" . $last_updated_id;
            echo "\n";
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
