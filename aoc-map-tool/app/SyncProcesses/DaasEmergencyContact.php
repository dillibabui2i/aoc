<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\MssqlProviders;
use Carbon\Carbon;

class DaasEmergencyContact
{
    /**
     * Sync Authorizations master table
     */
    public function syncEmergencyContact()
    {
        echo "Sync Emergency Contacts \n";
        $syncType = 'daas_emergencycontact';
        $tableName = 'client_emergency_contacts';


        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            exit;
        }
        $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
        var_dump($batch_info);
        echo "\n";

        $last_updated_date = $batch_info['last_updated_date'];
        $last_updated_id = $batch_info['last_updated_id'];
        $limit = $batch_info['batch_size'];
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $query = "select top " . $limit . "
            cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified,
            concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)),
                cast(coalesce(EC_CLIENT_ID,EC_INTAKE_ID) as varchar(50))) as CombinedString,
            AGENCY_ID as AGENCY_ID,
            cast(coalesce(EC_CLIENT_ID,EC_INTAKE_ID) as varchar(50)) as CLIENT_KEY,
            EC_CLIENT_ID as CLIENT_ID,
            EC_INTAKE_ID as INTAKE_ID,
            cast(EC_FIRST_NAME as varchar(150)) as FIRST_NAME,
            cast(EC_LAST_NAME as varchar(150)) as LAST_NAME,
            cast(EC_RELATION as varchar(150)) as RELATION,
            cast(EC_PRIORITY as varchar(150)) as PRIORITY,
            cast(EC_TELEPHONE as varchar(150)) as TELEPHONE,
            cast(EC_EMAIL as varchar(150)) as EMAIL,
            EC_IS_CLIENT_REPRSENTATIVE as IS_CLIENT_REPRSENTATIVE,
            ISVALIDRECORD as ISVALIDRECORD
            from CLIENTEMERGENCYCONTACTS_SVW
            WHERE CombinedString > '" . $combinedString ."'
            order by CombinedString ASC";
        $rawData = DB::connection('daas')->select(DB::raw($query));
        $data = json_decode(json_encode($rawData), true);
        $recordCount = 0;
        $updatedCount = 0;
        $insertedCount = 0;
        $deletedCount = 0;
        foreach($data as $row) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            $newRow = [];
            echo $row['LASTMODIFIED'] . "\n";
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $row['CLIENT_KEY'];
            echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            H::ExcelLog('Processing: ' . $row['CLIENT_ID'] . $row['INTAKE_ID'] .
                ' (' . $row['LASTMODIFIED'] . ')','daasEmergencyContactSync.log');
            H::ExcelLog(print_r($row,true),'daasEmergencyContactSync.log');
            //delete existing records for this client/intake
            $this->deleteExistingRecords($tableName, $row);
            //select * from snowflake where clientID or intakeID match
            $query2 = $this->buildQuery2($row);
            H::ExcelLog($query2,'daasEmergencyContactSync.log');
            if($query2!=''){
                //get and insert all of these records
                $rawData2 = DB::connection('daas')->select(DB::raw($query2));
                $data2 = json_decode(json_encode($rawData2), true);
                foreach($data2 as $row2) {
                    $newRow = $this->setNewRow($tableName, $row2, $newRow);
                    echo 'Insert Record' . "\n";
                    $insertedCount++;
                    $newRow['created_at'] = date_format(date_create(),
                        config('constant')['longdatetimeformat']);
                    $newRow['updated_at'] = date_format(date_create(),
                        config('constant')['longdatetimeformat']);
                    DB::table($tableName)->insert($newRow);
                    H::ExcelLog('Inserted: ' . print_r($newRow,true),'daasEmergencyContactSync.log');
                    }
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        echo "sync finished \n";
        H::ExcelLog("Records Processed: " . $recordCount ,'daasRenewablesSync.log');
        H::ExcelLog("Records Updated: " . $updatedCount,'daasRenewablesSync.log');
        H::ExcelLog("Records Inserted: " . $insertedCount,'daasRenewablesSync.log');
        H::ExcelLog("Records Deleted: " . $deletedCount,'daasRenewablesSync.log');
        H::getHelper('Sync')->stopBatch($syncType);
    }

    private function buildQuery2($row){
        $query2 = "select cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified,
        concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)),
            cast(coalesce(EC_CLIENT_ID,EC_INTAKE_ID) as varchar(50))) as CombinedString,
        AGENCY_ID as AGENCY_ID,
        coalesce(EC_CLIENT_ID,EC_INTAKE_ID) as CLIENT_KEY,
        EC_CLIENT_ID as CLIENT_ID,
        EC_INTAKE_ID as INTAKE_ID,
        cast(EC_FIRST_NAME as varchar(150)) as FIRST_NAME,
        cast(EC_LAST_NAME as varchar(150)) as LAST_NAME,
        cast(EC_RELATION as varchar(150)) as RELATION,
        cast(EC_PRIORITY as varchar(150)) as PRIORITY,
        cast(EC_TELEPHONE as varchar(150)) as TELEPHONE,
        cast(EC_EMAIL as varchar(150)) as EMAIL,
        EC_IS_CLIENT_REPRSENTATIVE as IS_CLIENT_REPRSENTATIVE,
        ISVALIDRECORD as ISVALIDRECORD
        from CLIENTEMERGENCYCONTACTS_SVW
        WHERE ISVALIDRECORD = 1 and (";
        $flage = 0;
        if(!is_null($row['CLIENT_ID']) && $row['CLIENT_ID'] != 0) {
            $query2 .= "CLIENT_ID = " . $row['CLIENT_ID'];
            $flage = 1;
        }
        if(!is_null($row['INTAKE_ID']) && $row['INTAKE_ID'] != 0) {
            if ($flage == 1) {
                $query2 .= " or INTAKE_ID = " . $row['INTAKE_ID'];
            } else {
                $query2 .= "INTAKE_ID = " . $row['INTAKE_ID'];
                $flage = 1;
            }
        }
        $query2 .= ')';
        if($flage!=1){
            $query2 = '';
        }
        echo $query2 . "\n";
        return $query2;
    }

    private function setNewRow($tableName, $row2, $newRow){
        foreach ($row2 as $key2 => &$value2) {
            if (is_null($value2)) {
                $value2 = '';
            }
            $newKey2 = str_replace(' ', '_', $key2);
            if (Schema::hasColumn($tableName, $newKey2)) {
                $newRow[$newKey2] = $value2;
            }
        }
        return $newRow;
    }

    private function deleteExistingRecords($tableName, $row){
        $q = DB::table($tableName);
        $q->where(function ($query) use ($row) {
            $flage = 0;
            if(!is_null($row['CLIENT_ID']) && $row['CLIENT_ID'] != 0) {
                $query->where('client_id',$row['CLIENT_ID']);
                $flage = 1;
            }
            if(!is_null($row['INTAKE_ID']) && $row['INTAKE_ID'] != 0) {
                if ($flage == 1) {
                    $query->orWhere('intake_id',$row['INTAKE_ID']);
                } else {
                    $query->where('intake_id',$row['INTAKE_ID']);
                }
            }
        });
        echo $q->toSql() . "\n";
        $result = $q->delete();
        var_dump($result);
        echo "\n";
        H::ExcelLog('Deleted ' . $result . ' records','daasEmergencyContactSync.log');
    }
}
