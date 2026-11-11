<?php

namespace App\Kantime\Services;

use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Repository\BigQuery\RenewablesTypesRepository;
use App\Kantime\Repository\MariaDB\RenewablesTypesRepository as MariadbRenewablesTypesRepository;
use Illuminate\Support\Facades\DB;

final class RenewablesTypeSyncService implements KantimeSyncServiceInterface
{

    private const LIMIT = 1000;

    public function __construct(
        private readonly RenewablesTypesRepository $repository,
        private readonly MariadbRenewablesTypesRepository $renewablesTypesRepository,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $tableName = 'renewables_types';
        try {
            foreach ($this->repository->getRecords(self::LIMIT, 0) as $row) {

                $dbRecord = $this->renewablesTypesRepository->get((int) $row['RENEW_ITEM_ID']);

                $newRow = [
                    'name'              => $row['RENEW_ITEM'],
                    'renewable_type_id' => $row['RENEW_ITEM_ID'],
                    'is_updated'        => '1',
                    'updated_at'        => date_format(date_create(), config('constant.longdatetimeformat') ?? 'Y-m-d H:i:s'),
                ];

                if (!empty($dbRecord)) {
                    $this->syncConfig->debugSync && logger()->info('Update Renewable Type Record ID: ' . $dbRecord[0]->id);
                    DB::table($tableName)
                        ->where('id', $dbRecord[0]->id)
                        ->update($newRow);
                } else {
                    $this->syncConfig->debugSync && logger()->info('Insert Renewable Type Record ID: ' . $newRow['renewable_type_id']);
                    $newRow['created_at'] = date_format(date_create(), config('constant.longdatetimeformat') ?? 'Y-m-d H:i:s');
                    DB::table($tableName)->insert($newRow);
                }
            }
            logger()->info('Renewable Type Records Sync Finished.');
        } catch (\Throwable $exception) {
            logger()->error($exception->getMessage());
        }
    }
}
