<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DaasAuthorizationsMaster
{
    /**
     * Sync Authorizations master table
     */
    public function syncAuthorizationsMaster()
    {
        echo "Sync Authorizations Master List \n";
        $syncType = 'daas_authorizations';
        $tableName = 'authorizations';
        $fieldName = 'AUTH_ID';

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
        $query = "select top " . $limit . " cast(ETL_UPDATED_TIMESTAMP as varchar(23)) as LastModified 
                    ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(23)), cast(AUTH_ID as varchar(20))) as CombinedString
                    ,AUTH_ID AS auth_id
                    ,AUTH_PAYER_ID AS payer_id
                    ,CLIENT_ID AS ClientID
                    ,AGENCY_BRANCH_ID AS BranchID
                    ,cast(AUTH_NUMBER as varchar(200)) AS number
                    ,AUTH_START_DATE AS start_date
                    ,AUTH_END_DATE AS end_date
                    ,AUTH_IS_VISITBASED AS is_visitbased
					,round(AUTH_TOTALUNITS,2) AS totalunits
					,AUTH_VISIT_COUNT AS visit_count
					,AUTH_SERVICE_CODE_ID AS service_code_id
                    ,cast(AUTH_DISCIPLINE_NAME as varchar(200)) AS discipline_name
                    ,round(AUTH_UNUSEDUNITS,2) AS unusedunits
                    ,round(AUTH_WEEKLY_LIMIT,2) AS weekly_limit
                    ,round(AUTH_MONTHLY_LIMIT,2) AS monthly_limit
					,round(AUTH_DAILY_LIMIT,2) AS daily_limit
                    ,AUTH_IS_AUTO AS is_auto
                    ,AGENCY_ID AS agency_id
                    ,AUTH_CREATED_DATE AS sf_created_at
					,cast(AUTH_PAYER_ALLOCATIONORDER as varchar(200)) AS payer_allocationorder
					,AUTH_IS_HOURLYBASED AS is_hourlybased
                    ,AUTH_ACKBY_CGID as auth_ack_by
                    ,AUTH_ACK_ON as auth_ack_date
                    ,AUTH_CREATED_DATE as auth_created_date
                    ,AUTH_ENTEREDBY_CGID as auth_entered_by
                    ,AUTH_PRIORITY_TYPE as priority_type
                    ,AUTH_RECEIVED_DATE as auth_received_date
                    ,AUTH_REQUESTEDBY_CGID as auth_requested_by
                    ,AUTH_REQUESTED_DATE as auth_requested_date
					,ETL_UPDATED_TIMESTAMP AS sf_updated_at
                    from AUTHORIZATIONMASTER_SVW 
                    WHERE CombinedString > '" . $combinedString ."' 
                    order by CombinedString ASC";
        echo $query . "\n";
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
				if ($newKey == 'CLIENTID') {
					$newKey = 'PatientID';
				}
				if ($newKey == 'BRANCHID') {
					$aocFacility = DB::table('mssql_facilities')->select("id")->where('BillingFacilityID',$value)->first();
					if (!empty($aocFacility)) {
						$value = $aocFacility->id;
					}else{
						$value = 0;
					}
					$newKey = 'aoc_facility_id';
				}
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
