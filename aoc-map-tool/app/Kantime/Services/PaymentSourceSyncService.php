<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\PaymentSourceRepository;
use App\Kantime\Repository\MariaDB\AocPaymentSourceRepository;

final class PaymentSourceSyncService implements KantimeSyncServiceInterface
{
    private const SYNC_TYPE = 'daas_paymentsource';
    private const UNIQUE_FIELD = 'payersource_id';

    public function __construct(
        private readonly PaymentSourceRepository $paymentSourceRepository,
        private readonly AocPaymentSourceRepository $aocPaymentSourceRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        echo "Sync Payment Source \n";

        $batchStatus = $this->sync->startBatch(self::SYNC_TYPE);

        if ($batchStatus == -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information(self::SYNC_TYPE);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] === '' ? 0 : (int) $batchInfo['last_updated_id'];
        $limit = (int) $batchInfo['batch_size'];
        $recordCount = 0;
        $combinedString = str_replace("'", "''", $lastUpdatedDate . $lastUpdatedId);
        $lastSuccessfulUpdatedDate = $lastUpdatedDate;
        $lastSuccessfulUpdatedId = $lastUpdatedId;
        $batchFailed = false;
        $exceptionMessage = null;

        try {
            foreach ($this->paymentSourceRepository->getRecords($limit, $combinedString) as $row) {
                $recordCount++;

                $this->syncConfig->debugSync && logger()->info('Processing PaymentSource Item row:', $row);
                $lastUpdatedDate = (string) ($row['lastmodified'] ?? $lastUpdatedDate);
                $lastUpdatedId = (int) ($row['payersource_id'] ?? 0);

                $payload = $this->toDbRow($row);
                $uniqueId = $payload[self::UNIQUE_FIELD] ?? null;

                if ($uniqueId === null) {
                    $this->syncConfig->debugSync && logger()->info('Skip row without payersource_id');
                    continue;
                }

                if ($this->aocPaymentSourceRepository->existsByPaymentSourceId($uniqueId)) {
                    echo "Update Record\n";
                    $this->syncConfig->debugSync && logger()->info('Processing PaymentSource Item row:', $row);
                    $this->aocPaymentSourceRepository->updateByPaymentSourceId($uniqueId, $payload);
                } else {
                    echo "Insert Record\n";
                    $this->aocPaymentSourceRepository->insert($payload);
                }

                $lastSuccessfulUpdatedDate = $lastUpdatedDate;
                $lastSuccessfulUpdatedId = $lastUpdatedId;
            }

            $this->syncConfig->debugSync && logger()->info('Processed PaymentSource rows: ' . $recordCount);
        } catch (\Throwable $e) {
            $batchFailed = true;
            $exceptionMessage = $e->getMessage();

            $this->sync->update_last_updated(
                self::SYNC_TYPE,
                $lastSuccessfulUpdatedDate,
                $lastSuccessfulUpdatedId
            );

            logger()->error('PaymentSource sync failed: ' . $exceptionMessage, [
                'last_successful_updated_date' => $lastSuccessfulUpdatedDate,
                'last_successful_updated_id' => $lastSuccessfulUpdatedId,
            ]);

            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Stopping PaymentSource batch', [
                'failed' => $batchFailed,
                'last_successful_updated_date' => $lastSuccessfulUpdatedDate,
                'last_successful_updated_id' => $lastSuccessfulUpdatedId,
                'exception_message' => $exceptionMessage,
            ]);
            $this->sync->update_last_updated(
                self::SYNC_TYPE,
                date_create($lastSuccessfulUpdatedDate)->format('Y-m-d H:i:s'),
                $lastSuccessfulUpdatedId
            );

            $this->sync->stopBatch(self::SYNC_TYPE);
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'payersource_id' => $row['payersource_id'] ?? null,
            'agency_id' => $row['agency_id'] ?? null,
            'name' => $row['name'] ?? null,
            'is_childcare' => $row['is_childcare'] ?? 0,
            'is_episodical' => $row['is_episodical'] ?? 0,
            'program_id' => $row['program_id'] ?? null,
            'program_type' => $row['program_type'] ?? null,
            'payergroup_id' => $row['payergroup_id'] ?? 0,
            'payergroup_name' => $row['payergroup_name'] ?? '',
            'caretype' => $row['caretype'] ?? null,
            'created_by' => 1,
            'updated_by' => 1,
            'updated_at' => now()->format('Y-m-d H:i:s'),
            'created_at' => now()->format('Y-m-d H:i:s'),
        ];
    }
}
