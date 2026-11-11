<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DaasPaymentSource
{
    /**
     * Sync Authorizations master table
     */
    public function syncPaymentSource()
    {
        echo "Sync Payment Source \n";
        $syncType = 'daas_paymentsource';
        $tableName = 'payment_source';
        $fieldName = 'PAYERSOURCE_ID';

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
        if($last_updated_id=='') {
            $last_updated_id = 0;
        }
        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];

        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $query = "select top " . $limit . " cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified 
                    ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)), cast(PS_PAYERSOURCEID as varchar(50))) as CombinedString
                    ,PS_PAYERSOURCEID AS payersource_id
                    ,AGENCY_ID AS agency_id
                    ,cast(PS_NAME as varchar(200)) AS name
                    ,PS_IS_CHILDCARE AS is_childcare
                    ,PS_IS_EPISODICAL AS is_episodical
                    ,PS_PROGRAM_ID AS program_id
                    ,cast(S_PROGRAM_TYPE as varchar(200)) AS program_type
                    ,PS_PAYERGROUP_ID AS payergroup_id
					,cast(PS_PAYERGROUP_NAME as varchar(200)) AS payergroup_name
					,cast(PS_CARETYPE as varchar(200)) AS caretype
                    from PAYMENTSOURCEMASTER_SVW 
                    WHERE CombinedString > '" . $combinedString ."' 
                    order by CombinedString ASC";
        $rawData = DB::connection('daas')->select(DB::raw($query)); 
        $data = json_decode(json_encode($rawData), true);
        $recordCount = 0;
        foreach($data as $row) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            echo $row['LASTMODIFIED'] . "\n";
            $newRow = [];
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $row[$fieldName];

            echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);
                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
				$newRow['created_by'] = 1;
				$newRow['updated_by'] = 1;
				$newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
				$newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            }
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                DB::table($tableName)->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }    
}
