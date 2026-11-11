<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ServiceCodesRepository;
use App\Kantime\Repository\MariaDB\AocServiceCodesRepository;

final class ServiceCodesSyncService implements KantimeSyncServiceInterface
{
    const SYNC_TYPE = 'daas_servicecodes';
    public function __construct(
        private readonly ServiceCodesRepository $serviceCodesRepository,
        private readonly AocServiceCodesRepository $aocServiceCodesRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = 'daas_servicecodes';
        $fieldName = 'service_id';

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
        $upserted = 0;

        try {
            foreach ($this->serviceCodesRepository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Service Codes Item:', $row);
                $dbRow = $this->toDbRow($row);

                if (!array_key_exists($fieldName, $dbRow) || empty($dbRow[$fieldName])) {
                    continue;
                }

                $lastSuccessfulId = $dbRow[$fieldName];
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                if (array_key_exists('isvalidrecord', $row) && !filter_var($row['isvalidrecord'], FILTER_VALIDATE_BOOLEAN)) {
                    $this->syncConfig->debugSync && logger()->info('Skipped invalid Service Codes Item: ' . $lastSuccessfulId);
                    continue;
                }

                $this->aocServiceCodesRepository->upsertByServiceId((int) $lastSuccessfulId, $dbRow);

                $this->syncConfig->debugSync && logger()->info('Successfully upserted Service Codes Item: ' . $lastSuccessfulId);
                $upserted++;
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            $this->sync->stopBatch($syncType);
            $this->aocServiceCodesRepository->postSyncUpdate($this, $upserted);
            logger()->info(sprintf('Service Codes Sync Finished on last updated ID `%s` with ETL `%s`' , $lastSuccessfulId, $lastUpdatedDate));
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'service_id' => (int) ($row['service_id'] ?? 0),
            'parent_serviceid' => (int) ($row['parent_serviceid'] ?? 0),
            'payerid' => (int) ($row['payerid'] ?? 0),
            'agency_id' => (int) ($row['agency_id'] ?? 0),
            'type' => $row['type'] ?? '',
            'name' => $row['name'] ?? null,
            'code' => $row['code'] ?? null,
            'paycode' => $row['paycode'] ?? '',
            'group_id' => (int) ($row['group_id'] ?? 0),
            'form_id' => (int) ($row['form_id'] ?? 0),
            'sf_created_by' => (int) ($row['sf_created_by'] ?? 0),
            'active' => (int) ($row['active'] ?? 0),
            'ContractualRate' => ($row['contractualrate'] ?? 0),
            'ContractualBillMode' => $row['contractualbillmode'] ?? null,
            'UnitType' => $row['unittype'] ?? '',
            'Unit' => (int) ($row['unit'] ?? 0),
            'MinuteInUnit' => (int) ($row['minuteinunit'] ?? 0),
            'Category' => (int) ($row['category'] ?? 0),
            'is_billable_daas' => (int) ($row['is_billable_daas'] ?? 0),
            'is_terminated' => (int) ($row['is_terminated'] ?? 0),
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}
