<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;

class MssqlCheckEtl
{
    /**
     * check for etl job successful record every morning - to know when to reset coldCall and textBlast and morningNTH
     * when checkETL returns a new successful job,  we reset process_settings for coldCall and textBlast and morningNTH and set
     * records in all of the daily refresh tables is_updated = 0.
     */
    public function checkETL()
    {
        echo "Check ETL \n";
        $syncType = 'checkETL';
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
        echo "do some sync stuff \n";

        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = "SELECT top 1 * FROM Excelss.GENERAL_LastETLJob 
			WHERE JobStartedOn  > '$last_updated_date'
			ORDER BY JobStartedOn DESC";

        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            var_dump($row);
            echo "\n";
            $last_updated_date = $row['JobStartedOn'];
            $last_updated_id = '';
            //first set all 3 maps to is_updated = 0
            $updateProcessSettings = [];
            $updateProcessSettings['is_updated'] = 0;
            DB::table('maps_coldcall')->update($updateProcessSettings);  //Cold Calling
            DB::table('maps_textblast')->update($updateProcessSettings); //Text Blast
            DB::table('maps_covclin')->update($updateProcessSettings);
            DB::table('maps_clients')->update($updateProcessSettings);
            DB::table('mssql_patients')->update($updateProcessSettings);
            DB::table('mssql_providers')->update($updateProcessSettings);
            DB::table('mssql_recruitertracker')->update($updateProcessSettings);
            DB::table('maps_therapy')->update($updateProcessSettings);
            DB::table('renewables')->update($updateProcessSettings);
            DB::table('paycom')->update($updateProcessSettings);

            //now reset all 3 maps to last_updated_id = '' or 0
            H::getHelper('Sync')->update_last_updated('morningNTH', '', '0');
            H::getHelper('Sync')->update_last_updated('coldCall', '', '');
            H::getHelper('Sync')->update_last_updated('textBlast', '', '');
            H::getHelper('Sync')->update_last_updated('clientListMap', '', '');
            H::getHelper('Sync')->update_last_updated('coverageClinicianMap', '', '');
            H::getHelper('Sync')->update_last_updated('providers', '', '');
            H::getHelper('Sync')->update_last_updated('patients', '', '');
            H::getHelper('Sync')->update_last_updated('tracker', '', '');
            H::getHelper('Sync')->update_last_updated('intake', '', 0);
            H::getHelper('Sync')->update_last_updated('schedulemaster', '', 0);
            H::getHelper('Sync')->update_last_updated('sm_yesterday', '', 0);
            H::getHelper('Sync')->update_last_updated('sm_lastweek', '', 0);
            H::getHelper('Sync')->update_last_updated('updateHoursWorked', '', 0);
            H::getHelper('Sync')->update_last_updated('therapy', '', 0);
            H::getHelper('Sync')->update_last_updated('renewables', '', 0);
            H::getHelper('Sync')->update_last_updated('servicecodes', '', 0);
            H::getHelper('Sync')->update_last_updated('referring_providers', '', 0);
            H::getHelper('Sync')->update_last_updated('paycom', '', 0);

            //now update the checkETL batch settings
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
            //just in case they send more than 1 record,  exit when done
            //break;
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
