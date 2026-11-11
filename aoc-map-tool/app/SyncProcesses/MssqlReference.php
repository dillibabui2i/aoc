<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlReference
{
    /**
     * Sync Reference Files - call each reference file process.
     */
    public function syncReference()
    {
        $this->syncFacilities();
        $this->syncEmploymentStatus();
        $this->syncPatientStatus();
    }

    /**
     * Test query to mssql.
     */
    public function queryMssql()
    {
        $conn = H::getHelper('Sync')->OpenConnection();

        $tsql = 'select * from [Excelss].[GENERAL_PatientStatus]';
        $tsql = "select * FROM Excelss.RecruiterTracker where Candidate = 'Shalanda Newman'";


        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error' . "\n";
            die();
        }
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            var_dump($row);
            echo "\n";
        }
    }

    /**
     * Sync Facilities.
     */
    public function syncFacilities()
    {
        echo "Sync Facilities List \n";
        $syncType = 'facilities';
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
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT * FROM [Excelss].[GENERAL_Facilities]';
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
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row['FacilityID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['FacilityID'];
            $dbRecord = DB::select("select * from mssql_facilities where `FacilityID` = '" . $uniqueID . "'");

            if (!empty($dbRecord)) {
                echo 'Update Facility' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_facilities', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                DB::table('mssql_facilities')->whereRaw('`FacilityID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Facility' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_facilities', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                DB::table('mssql_facilities')->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    /**
     * Sync Employment Status.
     */
    public function syncEmploymentStatus()
    {
        echo "Sync EmploymentStatus List \n";
        $syncType = 'mssql_employmentstatus';
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
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'SELECT * FROM [Excelss].[GENERAL_EmploymentStatus]';
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
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row['EmploymentStatusID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['EmploymentStatusID'];
            $dbRecord = DB::select("select * from mssql_employmentstatus where `EmploymentStatusID` = '" . $uniqueID . "'");

            if (!empty($dbRecord)) {
                echo 'Update EmploymentStatus' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_employmentstatus', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                DB::table('mssql_employmentstatus')->whereRaw('`EmploymentStatusID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert EmploymentStatus' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_employmentstatus', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                DB::table('mssql_employmentstatus')->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    /**
     * Sync Employment Status.
     */
    public function syncPatientStatus()
    {
        echo "Sync PatientStatus List \n";
        $syncType = 'mssql_patientstatus';
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
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $conn = H::getHelper('Sync')->OpenConnection();
        $tsql = 'select * from [Excelss].[GENERAL_PatientStatus]';
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
            $newRow = [];
            $last_updated_date = '';
            $last_updated_id = $row['PatientStatusID'];
            echo $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['PatientStatusID'];
            $dbRecord = DB::select("select * from mssql_patientstatus where `PatientStatusID` = '" . $uniqueID . "'");

            if (!empty($dbRecord)) {
                echo 'Update PatientStatus' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_patientstatus', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table('mssql_patientstatus')->whereRaw('`PatientStatusID`= ?', [$uniqueID])->update($newRow);
            } else {
                echo 'Insert PatientStatus' . "\n";
                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    if (Schema::hasColumn('mssql_patientstatus', $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table('mssql_patientstatus')->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);

        //******************************************************************************************* */
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }
}
