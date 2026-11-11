<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\SupervisoryFormRepository;
use App\Kantime\Repository\MariaDB\AocSupervisoryFormRepository;
use App\Kantime\Repository\MariaDB\SupervisoryVisitsRepository;

final class SupervisoryFormSyncService implements KantimeSyncServiceInterface
{
    const SYNC_TYPE = 'daas_supervisory_form_master';

    public function __construct(
        private readonly SupervisoryFormRepository $repository,
        private readonly AocSupervisoryFormRepository $aocRepository,
        private readonly SupervisoryVisitsRepository $supervisoryVisitsRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig,
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'echart_subformid';
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
        $processed = 0;
        $skipped = 0;

        try {
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $dbRow = $this->toDbRow($row);

                if (!array_key_exists($fieldName, $dbRow) || empty($dbRow[$fieldName])) {
                    $skipped++;
                    continue;
                }

                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByEchartSubformId((int) $lastSuccessfulId);
                    $this->supervisoryVisitsRepository->updateSupVisitNextDateFromDaas($row, 'Delete');
                    $skipped++;
                    continue;
                }

                $this->aocRepository->upsertByEchartSubformId((int) $lastSuccessfulId, $dbRow);
                $this->syncConfig->debugSync && logger()->info('Successfully upserted Supervisory Form Item: ' . $lastSuccessfulId);
                $processed++;
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully processed Supervisory Form Items: ', ['processedItems' => $processed, 'skipped' => $skipped]);
            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            $this->sync->stopBatch($syncType);
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'echart_subformid' => (int) ($row['echart_subformid'] ?? 0),
            'echart_id' => $row['echart_id'] ? ($row['echart_id'] !== '' ? $row['echart_id'] : null) : null,
            'schedule_id' => $row['schedule_id'] ? ($row['schedule_id'] !== '' ? $row['schedule_id'] : null) : null,
            'agency_id' => $row['agency_id'] ? ($row['agency_id'] !== '' ? $row['agency_id'] : null) : null,
            'client_id' => $row['client_id'] ? ($row['client_id'] !== '' ? $row['client_id'] : null) : null,
            'form_date' => $row['form_date'] ?? null,
            'form_status' => $row['form_status'] ?? null,
            'completedby_cgid' => $row['completedby_cgid'] ? ($row['completedby_cgid'] !== '' ? $row['completedby_cgid'] : null) : null,
            'supervisee_cgid' => $row['supervisee_cgid'] ? ($row['supervisee_cgid'] !== '' ? $row['supervisee_cgid'] : null) : null,
            'discipline' => $row['discipline'] ? ($row['discipline'] !== '' ? $row['discipline'] : null) : null,
            'daas_last_modified_timestamp' => $row['daas_last_modified_timestamp'] ?? null,
            'created_by' => 1,
            'isvalidrecord' => $row['isvalidrecord'],
        ];
    }
}
