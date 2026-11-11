<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ServiceCodeRatesRepository;
use App\Kantime\Repository\MariaDB\AocServiceCodeRatesRepository;

final class ServiceCodeRatesSyncService implements KantimeSyncServiceInterface
{
    const SYNC_TYPE = 'daas_servicecoderates';

    public function __construct(
        private readonly ServiceCodeRatesRepository $serviceCodeRatesRepository,
        private readonly AocServiceCodeRatesRepository $aocServiceCodeRatesRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'serviceRateId';
        $batchStatus = $this->sync->startBatch($syncType);

        if ($batchStatus === -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] ?: 0;
        $limit = $batchInfo['batch_size'];
        $combinedString = str_replace("'", "''", $lastUpdatedDate . $lastUpdatedId);
        $lastSuccessfulDate = $lastUpdatedDate;
        $lastSuccessfulId = $lastUpdatedId;
        $totalProcessed = 0;
        $itemsFailed = 0;
        $skipped = 0;

        try {
            foreach ($this->serviceCodeRatesRepository->getRecords($limit, $combinedString) as $row) {
                try {
                    $this->syncConfig->debugSync && logger()->info('Processing Service Code Rates Item:', $row);
                    $dbRow = $this->toDbRow($row);

                    if (!array_key_exists($fieldName, $dbRow) || empty($dbRow[$fieldName])) {
                        logger()->info('Skipping Service Code Rates Item:', $row);
                        $skipped++;
                        continue;
                    }

                    $lastSuccessfulId = $dbRow[$fieldName];
                    $this->aocServiceCodeRatesRepository->upsertByServiceRateId($lastSuccessfulId, $dbRow);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    $this->syncConfig->debugSync && logger()->info('Successfully upserted Service Code Rates Item: ' . $lastSuccessfulId);
                    $totalProcessed++;
                } catch (\Throwable $e) {
                    $itemsFailed++;
                    logger()->error($e);
                }
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            logger()->info('Successfully Processed Service Code Rates:', [
                'overall' => ($totalProcessed + $itemsFailed + $skipped),
                'processedItems' => $totalProcessed, 'itemsFailed' => $itemsFailed,
                'skipped' => $skipped,
            ]);
            $this->sync->stopBatch($syncType);
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'serviceRateId' => (int) ($row['servicerateid'] ?? 0),
            'serviceCodeId' => (int) ($row['servicecodeid'] ?? 0),
            'startDate' => $row['startdate'] ?? '0000-00-00 00:00:00',
            'endDate' => $row['enddate'] ?? '0000-00-00 00:00:00',
            'agency_id' => (int) ($row['agency_id'] ?? 0),
            'ContractualRate' => (float) ($row['contractualrate'] ?? 0),
            'ContractualBillMode' => trim(($row['contractualbillmode'] ?? null)),
            'UnitType' => trim(($row['unittype'] ?? null)),
            'Unit' => (int) ($row['unit'] ?? 0),
            'MinuteInUnit' => (int) ($row['minuteinunit'] ?? 0),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
