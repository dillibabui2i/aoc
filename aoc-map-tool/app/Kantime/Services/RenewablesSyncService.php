<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\DataMappers\RenewablesDataMapper;
use App\Kantime\Domain\ProviderId;
use App\Kantime\Domain\RenewableItem;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Exception\RenewablesSyncException;
use App\Kantime\Repository\KantimeRepositoryInterface;
use App\Kantime\Repository\MariaDB\ProvidersRepository;
use App\Kantime\Repository\MariaDB\RenewablesRepository;
use Throwable;

final class RenewablesSyncService implements KantimeSyncServiceInterface
{
    private const LIMIT = 1000;

    public function __construct(
        private readonly KantimeRepositoryInterface $repository,
        private readonly ProvidersRepository $providersRepository,
        private readonly RenewablesRepository $renewablesRepository,
        private readonly RenewablesDataMapper $renewablesDataMapper,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(?string $startDate = null, ?string $endDate = null, bool $force = false): void
    {
        $syncType = 'daas_renewables';

        $batchStatus = $this->sync->startBatch($syncType);
        if ($batchStatus == -1) {
            return;
        }

        $totalProcessed = 0;
        $itemsFailed = 0;

        try {
            $batchInfo = $this->sync->get_batch_information($syncType);
            $limit = (int) ($batchInfo['batch_size'] ?? self::LIMIT);
            $offset = 0;

            foreach ($this->repository->getRecords($limit, $offset, $startDate) as $item) {
                try {
                    if (!$item->isValidRecord) {
                        continue;
                    }

                    $this->processOne($item);
                    $this->sync->update_last_updated(
                        $syncType,
                        $item->lastModified,
                        $item->renewableItemId
                    );
                    $totalProcessed++;
                } catch (RenewablesSyncException $e) {
                    $itemsFailed++;
                    logger()->error($e->getMessage());
                }
            }
        } catch (Throwable $e) {
            throw new KantimeSyncException($e->getMessage(), 0, $e);
        } finally {
            logger()->info('Successfully Processed Renewables Sync:', [
                'overall' => ($totalProcessed + $itemsFailed),
                'processedItems' => $totalProcessed, 'itemsFailed' => $itemsFailed,
            ]);
            $this->sync->stopBatch($syncType);
        }
    }

    public function resync(array $providerIds)
    {
        $itemsFailed = [];
        try {
            $records = $this->repository->getRecordsByProviders($providerIds);
            foreach ($records as $record) {
                try {
                    if ($record instanceof RenewableItem) {
                        $this->processOne($record);
                    }
                } catch (RenewablesSyncException $e) {
                    $itemsFailed[] = $record->providerId;
                    logger()->error($e->getMessage());
                }
            }
        } catch (Throwable $e) {
            throw new KantimeSyncException($e->getMessage(), 0, $e);
        } finally {
            logger()->info('Successfully Processed Renewables Sync:', [
                'itemsFailed' => $itemsFailed,
            ]);
        }
    }

    public function syncOne(ProviderId $providerId)
    {
        try {
            $records = $this->repository->getRecordsById($providerId->getValue());
            foreach ($records as $record) {
                if ($record instanceof RenewableItem) {
                    $this->processOne($record);
                }
            }

        } catch (KantimeSyncException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new KantimeSyncException($e->getMessage(), 0, $e);
        }
    }

    private function processOne(RenewableItem $item): void
    {
        $provider = $this->providersRepository->get($item->providerId);

        if ($provider === null) {
            $this->syncConfig->debugSync && logger()
                ->info(sprintf('Renewables sync skipped for Provider ID: %s and will be synced after provider appeared', $item->providerId));

            return;
        }

        $aocProviderId = $provider->id;

        try {
            if ($item->isValidRecord === false) {
                $this->syncConfig->debugSync && logger()
                    ->info('Renewables Sync: Invalid record skipped', ['item' => $item->renewableItemId]);
                $this->renewablesRepository->deleteByRenewableItemId((int) $item->renewableItemId);

                return;
            }

            $dbRecord = $this->renewablesRepository->get($aocProviderId, (int) $item->renewablesType);
            $newRow = $this->renewablesDataMapper->toDbRow($item, $aocProviderId);
            $newRow['updated_by'] = 1;

            if (isset($dbRecord[0]->id)) {
                if ((int)$item->renewablesType === 17) {
                    return;
                }
                if (empty(array_diff_assoc($newRow, (array)$dbRecord[0]))) {
                    return;
                }

                $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
                $newRow['last_nursys_sync'] = '2000-01-01';
                $newRow['isMissing'] = empty($newRow['expiration_date']) || $newRow['expiration_date'] == '0000-00-00' ? 1 : 0;

                $this->renewablesRepository->updateById((int)$dbRecord[0]->id, $newRow);
                $this->syncConfig->debugSync && logger()->info('Renewables Sync: Updated renewable item id ' . $dbRecord[0]->id);

                return;
            }

            $newRow['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
            $newRow['last_nursys_sync'] = '2000-01-01';
            $newRow['isMissing'] = empty($newRow['expiration_date']) || $newRow['expiration_date'] == '0000-00-00' ? 1 : 0;

            $this->renewablesRepository->insert($newRow);
            $this->syncConfig->debugSync && logger()->info('Renewables Sync: Added renewable item with id ' . $item->renewableItemId);
        } catch (Throwable $e) {
            throw new RenewablesSyncException('Error Processing New Row: ' . $e->getMessage(), 0, $e);
        }
    }
}
