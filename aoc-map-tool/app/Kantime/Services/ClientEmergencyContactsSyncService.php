<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Repository\BigQuery\ClientEmergencyContactsRepository;
use App\Kantime\Repository\MariaDB\AocClientEmergencyContactsRepository;

final class ClientEmergencyContactsSyncService implements KantimeSyncServiceInterface
{
    private const SYNC_TYPE = 'daas_emergencycontact';

    public function __construct(
        private readonly ClientEmergencyContactsRepository $repository,
        private readonly AocClientEmergencyContactsRepository $clientEmergencyContactsRepository,
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
            //left the old approach to safely transfer from one to another
            $combinedString = $lastUpdatedDate !== '' && $lastUpdatedId !== null && $lastUpdatedId !== ''
                ? $lastUpdatedDate . '|' . $lastUpdatedId
                : $lastUpdatedDate;

            $currentGroupKey = null;
            $currentGroup = [];

            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $groupKey = (string) ($row['changeClientKey'] ?? '');

                if ($currentGroupKey === null) {
                    $currentGroupKey = $groupKey;
                }

                if ($groupKey !== $currentGroupKey) {
                    $this->processGroup($currentGroup, $syncType);

                    $currentGroup = [];
                    $currentGroupKey = $groupKey;
                }

                $currentGroup[] = $row;
            }

            if ($currentGroup !== []) {
                $this->processGroup($currentGroup, $syncType);
            }

        } catch (\Throwable $exception) {
            logger()->error($exception->getMessage());
        } finally {
            logger()->info('Client Emergency Contacts sync finished');
            $this->sync->stopBatch($syncType);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $group
     */
    private function processGroup(array $group, string $syncType): void
    {
        $firstRow = $group[0] ?? null;
        if ($firstRow === null) {
            return;
        }

        $this->deleteExistingRecords($firstRow);

        foreach ($group as $row) {
            $newRow = $this->mapToDbRow($row);
            $newRow['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $this->syncConfig->debugSync && logger()->info('Inserting Client Emergency Contacts row: ', $newRow);
            $this->clientEmergencyContactsRepository->insert($newRow);
        }

        $changeLastModified = (string) ($firstRow['changeLastModified'] ?? '');
        $changeClientKey = (string) ($firstRow['changeClientKey'] ?? '');

        if ($changeLastModified !== '' && $changeClientKey !== '') {
            $this->sync->update_last_updated(
                $syncType,
                $changeLastModified,
                $changeClientKey
            );
        }
    }

    private function deleteExistingRecords(array $row): void
    {
        $clientId = !is_null($row['syncClientId'] ?? null) && (int) $row['syncClientId'] !== 0
            ? (int) $row['syncClientId']
            : null;

        $intakeId = !is_null($row['syncIntakeId'] ?? null) && (int) $row['syncIntakeId'] !== 0
            ? (int) $row['syncIntakeId']
            : null;

        if ($clientId === null && $intakeId === null) {
            return;
        }
        $this->syncConfig->debugSync && logger()->info('Deleting Client Emergency Contacts row: ', $row);
        $this->clientEmergencyContactsRepository->deleteByClientIdAndIntakeId($clientId, $intakeId);
    }

    private function mapToDbRow(array $row): array
    {
        return [
            'agency_id' => $row['agencyId'] ?? '',
            'client_id' => $row['clientId'] ?? '',
            'intake_id' => $row['intakeId'] ?? '',
            'first_name' => $row['firstName'] ?? '',
            'last_name' => $row['lastName'] ?? '',
            'relation' => $row['relation'] ?? '',
            'priority' => $row['priority'] ?? '',
            'telephone' => $row['telephone'] ?? '',
            'email' => $row['email'] ?? '',
            'is_client_reprsentative' => $row['isClientRepresentative'] ?? '',
            'contact_id' => $row['contactId'] ?? '',
            'alternate_phone' => $row['alternatePhone'] ?? '',
            'work_phone' => $row['workPhone'] ?? '',
            'isvalidrecord' => $row['isValidRecord'] ? 1 : 0,
        ];
    }
}
