<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ReferralSourceRepository;
use App\Kantime\Repository\MariaDB\AocReferralSourceRepository;

final class ReferralSourceSyncService implements KantimeSyncServiceInterface
{
    const SYNC_TYPE = 'daas_referralsource_master';

    public function __construct(
        private readonly ReferralSourceRepository $repository,
        private readonly AocReferralSourceRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'source_id';
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

        try {
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Referral Source Item record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];
                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByReferralSourceId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Referral Source Item record: ' . $lastSuccessfulId);
                    continue;
                }

                $this->aocRepository->upsertByReferralSourceId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Referral Source Item record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Referral Source Items:', [
                'lastUpdatedDate' => $lastSuccessfulDate,
                'lastUpdatedId' => $lastSuccessfulId,
            ]);
            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            $this->sync->stopBatch($syncType);
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'source_id' => (int) ($row['source_id'] ?? 0),
            'agency_id' => (int) ($row['agency_id'] ?? 0),
            'agency_branch_id' => $row['agency_branch_id'] ? ($row['agency_branch_id'] !== '' ? $row['agency_branch_id'] : null) : null,
            'name' => $row['name'] ? ($row['name'] !== '' ? $row['name'] : null) : null,
            'admission_source' => $row['admission_source'] ? ($row['admission_source'] !== '' ? $row['admission_source'] : null) : null,
            'address_line1' => $row['address_line1'] ? ($row['address_line1'] !== '' ? $row['address_line1'] : null) : null,
            'address_line2' => $row['address_line2'] ? ($row['address_line2'] !== '' ? $row['address_line2'] : null) : null,
            'state' => $row['state'] ? ($row['state'] !== '' ? $row['state'] : null) : null,
            'zip' => $row['zip'] ? ($row['zip'] !== '' ? $row['zip'] : null) : null,
            'phone_no' => $row['phone_no'] ? ($row['phone_no'] !== '' ? $row['phone_no'] : null) : null,
            'mobile_no' => $row['mobile_no'] ? ($row['mobile_no'] !== '' ? $row['mobile_no'] : null) : null,
            'contact_name' => $row['contact_name'] ? ($row['contact_name'] !== '' ? $row['contact_name'] : null) : null,
            'contaxt_phone' => $row['contact_phone'] ? ($row['contact_phone'] !== '' ? $row['contact_phone'] : null) : null,
            'salesperson_id' => $row['salesperson_id'] ? ($row['salesperson_id'] !== '' ? $row['salesperson_id'] : null) : null,
            'category' => $row['category'] ?? null,
            'daas_last_modified_timestamp' => $row['daas_last_modified_timestamp'] ?? null,
            'created_by' => 1,
            'isvalidrecord' => $row['isvalidrecord'] ?? 1,
        ];
    }
}
