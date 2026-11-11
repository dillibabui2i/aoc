<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Repository\BigQuery\ClientPaymentSourceRepository;
use App\Kantime\Repository\MariaDB\AocClientPaymentSourceRepository;

final class ClientPaymentSourceSyncService implements KantimeSyncServiceInterface
{
    private const SYNC_TYPE = 'daas_client_payment_source';
    private const FIELD_NAME = 'csUniqueId';

    public function __construct(
        private readonly ClientPaymentSourceRepository $repository,
        private readonly AocClientPaymentSourceRepository $clientPaymentSourceRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;

        $batchStatus = $this->sync->startBatch($syncType);
        if ($batchStatus == -1) {
            return;
        }

        try {
            $batchInfo = $this->sync->get_batch_information($syncType);
            $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
            $lastUpdatedId = $batchInfo['last_updated_id'];
            $limit = (int) $batchInfo['batch_size'];
            $combinedString = $lastUpdatedDate . $lastUpdatedId;
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing record: ', $row);
                $lastModified = (string) ($row['lastModified'] ?? '');
                $lastUpdatedId = (string) ($row[self::FIELD_NAME] ?? '');

                if ($lastUpdatedId === '') {
                    continue;
                }

                $editType = $this->processRow($row);
                if ($editType === '') {
                    continue;
                }

                $this->sync->update_last_updated($syncType, $lastModified, $lastUpdatedId);

            }
        } finally {
            logger()->info('Client Payment Source sync finished');
            $this->sync->stopBatch($syncType);
        }
    }

    private function processRow(array $row): string
    {
        $newRow = $this->mapToDbRow($row);
        $uniqueId = $row[self::FIELD_NAME] ?? null;

        if ($uniqueId === null || $uniqueId === '') {
            return '';
        }

        $dbRecord = $this->clientPaymentSourceRepository->getByUniqueId((string) $uniqueId);

        $isValidRecord = $row['isValidRecord'] ?? null;
        $isValid = filter_var($isValidRecord, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isValid === false || (is_numeric($isValidRecord) && (int) $isValidRecord === 0)) {
            $this->clientPaymentSourceRepository->deleteByUniqueId((string) $uniqueId);
            return 'D';
        }

        if ($dbRecord !== null) {
            $newRow['updated_by'] = 1;
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $this->clientPaymentSourceRepository->updateByUniqueId((string) $uniqueId, $newRow);
            return 'E';
        }

        $newRow['created_by'] = 1;
        $newRow['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
        $this->clientPaymentSourceRepository->insert($newRow);

        return 'I';
    }

    private function mapToDbRow(array $row): array
    {
        return [
            'agency_id' => $row['agencyId'] ?? null,
            'client_id' => $row['clientId'] ?? null,
            'intake_id' => $row['intakeId'] ?? null,
            'is_primary' => is_bool($row['clientPsIsPrimary'] ?? null) ? (int) $row['clientPsIsPrimary'] : ($row['clientPsIsPrimary'] ?? null),
            'payertype' => $row['clientPsPayertype'] ?? null,
            'start_date' => $row['clientPsStartDate'] ?? null,
            'end_date' => $row['clientPsEndDate'] ?? null,
            'effectiveperiod' => $row['clientPsEffectiveperiod'] ?? null,
            'primary_id' => $row['clientPsPrimaryId'] ? ($row['clientPsPrimaryId'] !== '' ? $row['clientPsPrimaryId'] : null) :  null,
            'parent_id' => $row['clientPsParentId'] ? ($row['clientPsParentId'] !== '' ? $row['clientPsParentId'] : null) :  null,
            'primary_payer_id' => $row['clientPsPrimaryPayerId'] ?? null,
            'additional_payer_id' => $row['clientPsAdditionalPayerId'] ?? null,
            'unique_id' => $row['clientPsUniqueId'] ?? null,
            'daas_last_modified_timestamp' => $row['daasLastModifiedTimestamp'] ?? null,
            'clent_ps_insurance_no' => $row['clentPsInsuranceNo'] ? ($row['clentPsInsuranceNo'] !== '' ? $row['clentPsInsuranceNo'] : null) :  null,
            'isvalidrecord' => is_bool($row['isValidRecord'] ?? null) ? (int) $row['isValidRecord'] : ($row['isValidRecord'] ?? null),
            'etl_updated_timestamp' => $row['etlUpdatedTimestamp'] ?? null,
            'effperiod_internal_id' => $row['clientPsEffperiodInternalId'] ?? null,
            'primary_internal_id' => $row['clientPsPrimaryInternalId'] ?? null,
            'addpayer_internal_id' => $row['clientPsAddpayerInternalId'] ?? null,
            'cs_unique_id' => $row['csUniqueId'] ?? null,
        ];
    }
}
