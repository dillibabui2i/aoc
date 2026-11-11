<?php

namespace App\Jobs\Renewables;

use App\Helpers\H;
use App\Jobs\Job;
use App\SyncProcesses\Exceptions\KantimeSyncException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SyncRenewablesTypesJob extends Job
{
    public function __construct()
    {
    }

    public function handle(
    ): void {
        echo "Sync Renewable Types List \n";
        $syncType = 'daas_renewables_types';
        $tableName = 'renewables_types';

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

        $last_updated_date = $batch_info['last_updated_date'] ?? '';
        $last_updated_id   = $batch_info['last_updated_id'] ?? '';

        $recordCount = 0;

        try {
            $query = "
                SELECT DISTINCT
                    RENEW_ITEM_ID,
                    RENEW_ITEM
                FROM RENEWABLEITEMS_SVW
                ORDER BY RENEW_ITEM_ID ASC
            ";

            echo $query . "\n";

            $rawData = DB::connection('daas')->select(DB::raw($query));
            $data = json_decode(json_encode($rawData), true);

            foreach ($data as $row) {
                $recordCount++;
                $last_updated_date = '';
                $last_updated_id   = $row['RENEW_ITEM_ID'];

                echo $last_updated_date . ' - ' . $last_updated_id . "\n";

                $dbRecord = DB::select("SELECT * FROM $tableName WHERE renewable_type_id = ?", [$row['RENEW_ITEM_ID']]);

                $newRow = [
                    'name'              => $row['RENEW_ITEM'],
                    'renewable_type_id' => $row['RENEW_ITEM_ID'],
                    'is_updated'        => '1',
                    'updated_at'        => date_format(date_create(), config('constant.longdatetimeformat') ?? 'Y-m-d H:i:s'),
                ];

                if (!empty($dbRecord)) {
                    echo 'Update Record' . "\n";
                    DB::table($tableName)
                        ->where('id', $dbRecord[0]->id)
                        ->update($newRow);
                } else {
                    echo 'Insert Record' . "\n";
                    $newRow['created_at'] = date_format(date_create(), config('constant.longdatetimeformat') ?? 'Y-m-d H:i:s');
                    DB::table($tableName)->insert($newRow);
                }

                H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
            }

            //if no records were found, delete all rows with 'is_updated = 0' and stop from processing for rest of day
            if ($recordCount == 0) {
                H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
                DB::table($tableName)
                    ->where('is_updated', 0)
                    ->update(['is_deleted' => 1]);
                echo "No types found → marked as done for the day and soft-deleted old records\n";
            }

            echo "Sync Renewable Types finished\n";

        } catch (\Exception $e) {
            echo "Sync Error: " . $e->getMessage() . "\n";
            Log::critical('RenewableTypes sync error: ' . $e->getMessage());
            throw new KantimeSyncException($e->getMessage());
        } finally {
            H::getHelper('Sync')->stopBatch($syncType);
        }
    }

    public function failed(Throwable $t): void
    {
        Log::error('SyncRenewableTypesJob', [
            'massage' => $t->getMessage(),
            'exception' => $t,
        ]);
    }
}
