<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Jobs\Renewables\SyncRenewablesTypesJob;
use App\MssqlProviders;
use App\SyncProcesses\Exceptions\KantimeSyncException;
use App\SyncProcesses\Exceptions\RenewablesSyncException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\Console\Command\Command;
use Illuminate\Support\Facades\Storage;

/** @deprecated Use RenewablesSyncService instead */
class DaasRenewables
{
    /**
     * Sync Authorizations master table.
     */
    public function syncRenewables()
    {
        echo "Sync Renewables \n";
        $syncType = 'daas_renewables';
        $tableName = 'renewables';
        $fieldName = 'renewable_item_id';

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

        if ($last_updated_id == '') {
            $last_updated_id = 0;
        }

        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];
        // date > current date works perfectly for SF to get only today's records it's better not rely on hours as we have sync delay and possibly different timezone
        // in BigQuery integration KT could switch to UTC.
        // Appeared that we have providers synced to AZURE on time at midnight, that's why we have renewables missing for added providers today (as we're skipping them for missing providers).
        // Let's expand rescan for two days.
        $etlDate = date('Y-m-d', strtotime('yesterday'));
        /* Not that many renewables changes populated during the day according to SF history (3-6k a week), so we can go without limit (tested 5+ rows in batch)
         also there is no harm updating all through the day, as no internal data updated, the updated dates are taken from SF, and we are skipping possible issue missing
         come renewables that become available during the day and ID is less than last updated */
        $query = 'select top ' . $limit . "
                        cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified
                        ,concat(
                            cast(RENEW_CLINICIAN_ID as varchar(50)),
                            cast(RENEW_ITEM as varchar(100))
                        ) as UNIQUE_KEY
                        ,AGENCY_ID AS HHA
                        ,cast(ISVALIDRECORD as varchar(10)) as ISVALIDRECORD
                        ,cast(RENEW_CLINICIAN_ID as varchar(50)) as RENEW_CLINICIAN_ID
                        ,RENEW_EMPLOYEE_ID as ProviderID
                        ,RENEW_EXPIRY_DATE as expiration_date
                        ,RENEW_INTERNAL_ID as renewable_item_id
                        ,RENEW_ISSUE_DATE as issued_date
                        ,RENEW_IS_MANDATORY as mandatory
                        ,RENEW_ITEM_ID as renewables_type
                        ,cast(RENEW_ITEM as varchar(100)) as renew_item
                        ,cast(RENEW_LICENSE_NO as varchar(100)) as license_no
                        ,cast(RENEW_STATE as varchar(20)) as license_state
                from RENEWABLEITEMS_SVW
                where ETL_UPDATED_TIMESTAMP > '{$etlDate}'
                order by RENEW_INTERNAL_ID ASC";

        try {
            $rawData = DB::connection('daas')->select(DB::raw($query));
        } catch (\Exception $e) {
            H::getHelper('Sync')->stopBatch($syncType);
            throw new KantimeSyncException($e->getMessage());
        }

        $data = json_decode(json_encode($rawData), true);
        $recordCount = 0;
        $updatedCount = 0;
        $insertedCount = 0;
        $deletedCount = 0;
        $syncErrorsCount = 0;
        $skippedDueToError = [];
        //adding a bit more safety to the process
        try {
            foreach ($data as $row) {
                try {
                    $recordCount++;
                    var_dump($row);
                    echo "\n";
                    $newRow = [];
                    echo $row['LASTMODIFIED'] . "\n";
                    $last_updated_date = $row['LASTMODIFIED'];
                    $last_updated_id = $row['RENEWABLE_ITEM_ID'];
                    echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
                    H::ExcelLog('Processing: ' . $row['RENEWABLE_ITEM_ID'] . ' (' .
                        $row['LASTMODIFIED'] . ')', 'daasRenewablesSync.log');
                    H::ExcelLog(print_r($row, true), 'daasRenewablesSync.log');
                    //get mssql_providers.id
                    $findProvider = MssqlProviders::where('BillingProviderID', $row['PROVIDERID'])->get();

                    if (isset($findProvider[0]->id)) {
                        $aoc_provider_id = $findProvider[0]->id;
                        $newRow['aoc_provider_id'] = $aoc_provider_id;
                        echo 'aoc_provider_id:  ' . $aoc_provider_id;

                        try {
                            $dbRecord = DB::table('renewables')->where('aoc_provider_id', $aoc_provider_id)
                                ->where('renewables_type', $row['RENEWABLES_TYPE'])
                                ->orderBy('id')->get();
                        } catch (\Exception $e) {
                            throw new RenewablesSyncException('Error fetching renewables from AOC DB: ' . $e->getMessage());
                        }

                        try {
                            $newRow = $this->setNewRow($tableName, $row, $newRow);
                            var_dump($newRow);
                            echo "\n";
                            $newRow['updated_by'] = 1;
                            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
                            $editType = $this->processRow($tableName, $fieldName, $row, $newRow, $dbRecord);

                            switch ($editType) {
                                case 'D':
                                    $updatedCount++;
                                    break;
                                case 'E':
                                    $insertedCount++;
                                    break;
                                case 'I':
                                    $deletedCount++;
                                    break;
                                default:
                                    // there are only Delete, Edit, and Inserts
                                    break;
                            }
                        } catch (\Exception $e) {
                            throw new RenewablesSyncException('Error Processing New Row: ' . $e->getMessage());
                        }
                    } else {
                        H::ExcelLog('skipped because provider not found: ' . print_r($row, true), 'daasRenewablesSync.log');
                    }

                    H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
                } catch (RenewablesSyncException $e) {
                    $syncErrorsCount++;
                    $skippedDueToError[] = $last_updated_id;
                    H::ExcelLog(sprintf('Item %s sync skipped due to error: %s', $last_updated_id, $e->getMessage()), 'daasRenewablesSync.log');
                    Log::critical($e->getMessage());
                } catch (\Exception $e) {
                    throw new KantimeSyncException($e->getMessage());
                }
            }
            echo "Sync Finished \n";
        } catch (KantimeSyncException $e) {
            echo "Sync Error \n";
            echo $e->getMessage();
            Log::critical($e->getMessage());
        } finally {
            H::ExcelLog('Records Processed: ' . $recordCount, 'daasRenewablesSync.log');
            H::ExcelLog('Records Updated: ' . $updatedCount, 'daasRenewablesSync.log');
            H::ExcelLog('Records Inserted: ' . $insertedCount, 'daasRenewablesSync.log');
            H::ExcelLog('Records Deleted: ' . $deletedCount, 'daasRenewablesSync.log');
            H::ExcelLog('Sync Errors: ' . $syncErrorsCount, 'daasRenewablesSync.log');
            H::ExcelLog(sprintf('Renewables skipped due to error: [%s]', implode(', ', $skippedDueToError)), 'daasRenewablesSync.log');
            H::getHelper('Sync')->stopBatch($syncType);
        }
    }

    private function setNewRow($tableName, $row, $newRow)
    {
        foreach ($row as $key => &$value) {
            if (is_null($value)) {
                $value = '';
            }
            $newKey = str_replace(' ', '_', $key);
            H::ExcelLog($key . ' -> ' . $newKey, 'daasRenewablesSync.log');
            if (Schema::hasColumn($tableName, $newKey)) {
                $newRow[$newKey] = $value;
            }
        }
        $newRow['isMissing'] = 0;

        return $newRow;
    }

    private function processRow($tableName, $fieldName, $row, $newRow, $dbRecord)
    {
        if (isset($row['ISVALIDRECORD']) && strtolower($row['ISVALIDRECORD']) === 'false') {
            echo 'Deleted Record ' . $row['RENEWABLE_ITEM_ID'] . "\n";
            DB::table($tableName)->where($fieldName, $row['RENEWABLE_ITEM_ID'])->delete();
            H::ExcelLog(print_r($newRow, true), 'daasRenewablesSyncDeleted.log');
            $editType = 'D';
        } elseif (isset($dbRecord[0]->id)) {
            $this->updateRow($tableName, $row, $newRow, $dbRecord);
            $editType = 'E';
        } else {
            echo 'Insert Record' . "\n";
            $newRow['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['last_nursys_sync'] = '2000-01-01';
            DB::table($tableName)->insert($newRow);
            H::ExcelLog('Inserted: ' . $row['RENEWABLE_ITEM_ID'] . ' (' .
                $row['LASTMODIFIED'] . ')', 'daasRenewablesSync.log');
            $editType = 'I';
        }

        return $editType;
    }

    private function updateRow($tableName, $row, $newRow, $dbRecord)
    {
        //do not ever update ANNUAL PERFORMANCE EVALUATION because we update them in the app
        if ($row['RENEWABLES_TYPE'] != 17) {
            echo 'Update Record' . "\n";
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['last_nursys_sync'] = '2000-01-01';
            DB::table($tableName)
                ->where('id', $dbRecord[0]->id)
                ->update($newRow);
            H::ExcelLog('Updated: ' . $dbRecord[0]->id . ' (' .
                $row['LASTMODIFIED'] . ')', 'daasRenewablesSync.log');
        } else {
            echo 'Skip record because type is Evaluation' . "\n";
            H::ExcelLog('Skipped: ' . $dbRecord[0]->id .
                ' because it is ANNUAL PERFORMANCE EVALUATION', 'daasRenewablesSync.log');
        }
    }

    public function runFullResync($syncType)
    {
        $tableName = 'renewables';
        $fieldName = 'renewable_item_id';

        $batch_info = DB::table('process_settings')->where('process', $syncType)->first();
        $last_updated_id = $batch_info->last_updated_id ?? 0;
        $limit = $batch_info->batch_size ?? 1000;

        $recordCount = 0;

        while (true) {
            $query = "
            SELECT TOP $limit
                cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified,
                cast(RENEW_INTERNAL_ID as int) as renewable_item_id,
                cast(ISVALIDRECORD as varchar(10)) as ISVALIDRECORD,
                cast(RENEW_CLINICIAN_ID as varchar(50)) as RENEW_CLINICIAN_ID,
                RENEW_EMPLOYEE_ID as ProviderID,
                RENEW_EXPIRY_DATE as expiration_date,
                RENEW_ISSUE_DATE as issued_date,
                RENEW_IS_MANDATORY as mandatory,
                RENEW_ITEM_ID as renewables_type,
                cast(RENEW_ITEM as varchar(100)) as renew_item,
                cast(RENEW_LICENSE_NO as varchar(100)) as license_no,
                cast(RENEW_STATE as varchar(20)) as license_state
            FROM RENEWABLEITEMS_SVW
            WHERE RENEW_INTERNAL_ID > $last_updated_id
            ORDER BY RENEW_INTERNAL_ID ASC
        ";

            $raw = DB::connection('daas')->select($query);
            $data = json_decode(json_encode($raw), true);

            if (empty($data)) {
                echo "No more data. Full resync complete.\n";
                break;
            }

            foreach ($data as $row) {
                $recordCount++;
                $last_updated_id = $row['RENEWABLE_ITEM_ID'];

                // ---- NEW LOGIC (correct) ----

                $newRow = [];
                $newRow = $this->setNewRow($tableName, $row, $newRow);

                $newRow['updated_by'] = 1;
                $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);

                $findProvider = MssqlProviders::where('BillingProviderID', $row['PROVIDERID'])->first();

                if ($findProvider) {
                    $newRow['aoc_provider_id'] = $findProvider->id;

                    $dbRecord = DB::table('renewables')
                        ->where('aoc_provider_id', $findProvider->id)
                        ->where('renewables_type', $row['RENEWABLES_TYPE'])
                        ->orderBy('id')
                        ->get();

                    $this->processRow($tableName, $fieldName, $row, $newRow, $dbRecord);
                }

                DB::table('process_settings')
                    ->where('process', $syncType)
                    ->update([
                        'last_updated_id' => $last_updated_id,
                        'last_updated_date' => $row['LASTMODIFIED'],
                    ]);
            }

            echo "Processed $recordCount rows (last ID: $last_updated_id)\n";
            usleep(200000);
        }
    }
    /**
     * Sync Renewable Types from Snowflake
     */
    public function syncRenewableTypes(): int
    {
        dispatch(new SyncRenewablesTypesJob());

        return Command::SUCCESS;
    }
}
