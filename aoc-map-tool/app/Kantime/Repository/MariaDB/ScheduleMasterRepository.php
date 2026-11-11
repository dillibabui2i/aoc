<?php

namespace App\Kantime\Repository\MariaDB;

use App\Kantime\DataMappers\ScheduleMasterDataMapper;
use App\Kantime\Domain\ScheduleMasterItem;
use Illuminate\Support\Facades\DB;

final class ScheduleMasterRepository
{
    const TABLE_NAME = 'mssql_schedulemaster';

    public function __construct(private readonly ScheduleMasterDataMapper $mapper)
    {
    }

    public function create(ScheduleMasterItem $item): void
    {
        $row = $this->mapper->toDbRow($item);
        $row['is_updated'] = '1';
        $row['updated_at'] = date('Y-m-d H:i:s');
        $row['checked_off_at'] = date('Y-m-d H:i:s');
        $row['Modifier4'] = '';

        if (($item->lastModified ?? null) !== null) {
            $row['etl_updated_timestamp'] = (string) $item->updated_at;
        }
        $row['is_no_provider'] = empty($item->ProviderID) ? 1 : 0;
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(ScheduleMasterItem $item) : bool
    {
        $fieldName = 'CGTaskID';
        $newRow = $this->mapper->toDbRow($item);
        $dbRecord = DB::table(self::TABLE_NAME)->where($fieldName, $item->CGTaskID)->first();

        if ($dbRecord) {
            // Skip updating if record already marked deleted
            if ((int) ($dbRecord->is_deleted ?? 0) === 1) {
                return false;
            }

            $newRow['is_updated'] = '1';
            $newRow['updated_at'] = date('Y-m-d H:i:s');

            // Append ETL timestamp into Modifier4 history like old job
            $etlTs = ($item->etl_updated_timestamp ?? '');
            $tmpETL = (string) ($dbRecord->Modifier4 ?? '');
            if ($etlTs !== '') {
                if ($tmpETL !== '') {
                    $tmpETL .= ' , ';
                }
                $newRow['Modifier4'] = $tmpETL . $etlTs;
            }

            // Update is_no_provider value (ported from old job)
            $providerId = (string) ($item->ProviderID ?? '');
            $scheduleStatusId = (int) ($item->ScheduleStatusID ?? 0);
            if ($providerId !== '' && (int) ($dbRecord->is_no_provider ?? 0) === 2 && $scheduleStatusId === 5) {
                $newRow['is_no_provider'] = 1;
            }
            if ($scheduleStatusId !== 5) {
                $newRow['is_no_provider'] = 0;
            }

            DB::table(self::TABLE_NAME)->where($fieldName, $item->CGTaskID)->update($newRow);

            return true;
        }
        return false;
    }

    public function updateOrInsert(ScheduleMasterItem $item): void
    {
        $fieldName = 'CGTaskID';
        $newRow = $this->mapper->toDbRow($item);
        $dbRecord = DB::table(self::TABLE_NAME)->where($fieldName, $item->CGTaskID)->first();

        if ($dbRecord) {
            $this->update($item);
        } else {
            $this->create($item);
        }
    }

    public function delete($id): void
    {
        $fieldName = 'CGTaskID';
        DB::table(self::TABLE_NAME)->where($fieldName, $id)->delete();
    }

    /**
     * Current values of the given columns for a schedule, null if it is not in the table.
     */
    public function findByCgTaskId(int $cgTaskId, array $columns): ?object
    {
        return DB::table(self::TABLE_NAME)->where('CGTaskID', $cgTaskId)->first($columns);
    }

    /**
     * CGTaskID => is_deleted for the given ids (1 if any local row with that CGTaskID is deleted);
     * ids missing from the table are absent.
     */
    public function getDeletedFlagsByCgTaskIds(array $cgTaskIds): array
    {
        $flags = [];

        foreach (array_chunk($cgTaskIds, 1000) as $chunk) {
            $rows = DB::table(self::TABLE_NAME)
                ->whereIn('CGTaskID', $chunk)
                ->groupBy('CGTaskID')
                ->selectRaw('CGTaskID, MAX(IFNULL(is_deleted, 0)) AS is_deleted')
                ->get();

            foreach ($rows as $row) {
                $flags[(int) $row->CGTaskID] = (int) $row->is_deleted;
            }
        }

        return $flags;
    }
}
