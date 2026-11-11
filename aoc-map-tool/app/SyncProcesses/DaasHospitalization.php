<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\MssqlProviders;
use Carbon\Carbon;

class DaasHospitalization
{
    /**
     * Sync Authorizations master table
     */
    public function syncHospitalizationMaster()
    {
        echo "Sync Hospitalization \n";
        $syncType = 'daas_hospitalization';
        $tableName = 'hospitalization_report_master';
        $fieldName = 'unique_key';


        //start batch
        $batch_status = H::getHelper('Sync')->startBatch($syncType);
        echo $batch_status . "\n";
        if ($batch_status == -1) {
            echo 'stop process';
            //exit;
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
            concat(cast(HOS_CONTEXT_TYPE as varchar(10)),cast(HOS_OBJECT_ID as varchar(100))) as UNIQUE_KEY
            ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)),
            cast(HOS_CONTEXT_TYPE as varchar(10)),cast(HOS_OBJECT_ID as varchar(100))) as CombinedString,
            AGENCY_ID as AGENCY_ID , HOS_CLIENT_ID as CLIENT_ID , HOS_OBJECT_ID as OBJECT_ID,
            HOS_CONTEXT_TYPE as CONTEXT_TYPE,
            HOS_INITITALSOCROC_ASSESSMENT_DATE as INITITALSOCROC_ASSESSMENT_DATE,
            cast(HOS_PRIMARY_DIAGNOSIS as varchar(1000)) as PRIMARY_DIAGNOSIS,
            HOS_TRANSFERORONHOLD_DATE as TRANSFERORONHOLD_DATE,
            HOS_ROCORRESUME_DATE as ROCORRESUME_DATE,
            HOS_DISCHARGE_DATE as DISCHARGE_DATE,
            HOS_DAYSATHOME as DAYSATHOME,
            HOS_DAYSATHOSPITAL as DAYSATHOSPITAL,
            cast(HOS_TYPEOFFACILITY as varchar(1000)) as TYPEOFFACILITY,
            cast(HOS_FACILITY_TRANSFERREDTO as varchar(1000)) as FACILITY_TRANSFERREDTO,
            cast(HOS_REASON_ID as varchar(1000)) as REASON_ID,
            cast(HOS_REASON_DESC as varchar(1000)) as REASON_DESC,
            HOS_EPISODE_NUMBER as EPISODE_NUMBER,
            HOS_EPISODE_STARTDATE as EPISODE_STARTDATE,
            HOS_EPISODE_ENDDATE as EPISODE_ENDDATE,
            cast(HOS_OTHER_DIAGNOSIS as varchar(1000)) as OTHER_DIAGNOSIS,
            cast(HOS_SECONDARY_PAYER as varchar(1000)) as SECONDARY_PAYER,
            cast(HOS_TERTIARYPAYER as varchar(1000)) as TERTIARYPAYER,
            HOS_START_DATE as START_DATE , HOS_END_DATE as END_DATE,
            cast(HOS_SURGICAL_DIAGNOSIS as varchar(1000)) as SURGICAL_DIAGNOSIS,
            cast(HOS_BILLINGACUITY_EFFECTIVEPERIOD as varchar(1000)) as BILLINGACUITY_EFFECTIVEPERIOD,
            cast(HOS_DISCHARGE_DX as varchar(1000)) as DISCHARGE_DX,
            cast(HOS_DISCHARGE_SEC_DX as varchar(1000)) as DISCHARGE_SEC_DX,
            HOS_HOSP_DISCHARGE_DATE as HOSP_DISCHARGE_DATE,
            cast(HOS_HOSP_DISCHARGE_TIME as varchar(1000)) as HOSP_DISCHARGE_TIME,
            HOS_RESUMPTION_DATE as RESUMPTION_DATE,
            HOS_STAYATHOME_DAYS as STAYATHOME_DAYS,
            HOS_STAYATHOME_MINUTES as STAYATHOME_MINUTES,
            HOS_STAYATHOSPITAL_DAYS as STAYATHOSPITAL_DAYS,
            HOS_STAYATHOSPITAL_MINUTES as STAYATHOSPITAL_MINUTES,
            cast(HOS_TYPE as varchar(1000)) as TYPE,
            HOS_HOSPITALIZATION_DATE as HOSPITALIZATION_DATE,
            ISVALIDRECORD as ISVALIDRECORD
            from HOSPITALIZATIONREPORTMASTER_SVW
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
            $uniqueID = $row['UNIQUE_KEY'];
            echo $row['LASTMODIFIED'] . "\n";
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $uniqueID;
            echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            H::ExcelLog('Processing: ' . $row['UNIQUE_KEY'] .
                ' (' . $row['LASTMODIFIED'] . ')','daasHospitalizationSync.log');
            H::ExcelLog(print_r($row,true),'daasHospitalizationSync.log');
            $dbRecord = DB::table($tableName)->where("unique_key",$uniqueID)->first();
            var_dump($dbRecord);
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);
                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
            }
            $newRow['updated_by'] = 1;
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            if (isset($row['ISVALIDRECORD']) && $row['ISVALIDRECORD'] == 'false') {
                echo 'Deleted Record ' . $uniqueID . "\n";
                DB::table($tableName)->where($fieldName, $uniqueID)->delete();
                H::ExcelLog(print_r($newRow,true),'daasRenewablesSyncDeleted.log');
            } elseif (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                $updatedCount++;
                $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
                DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
                H::ExcelLog('Updated: ' . $uniqueID . ' (' . $row['LASTMODIFIED'] . ')','daasRenewablesSync.log');
            } else {
                echo 'Insert Record' . "\n";
                $insertedCount++;
                $newRow['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
                $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
                DB::table($tableName)->insert($newRow);
                H::ExcelLog('Inserted: ' . $uniqueID . ' (' . $row['LASTMODIFIED'] . ')','daasRenewablesSync.log');
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

}
