<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocSupervisoryFormRepository
{
    const TABLE_NAME = 'supervisory_form_master';

    public function __construct(private readonly SupervisoryVisitsRepository $supervisoryVisitsRepository)
    {
    }


    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $uniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('echart_subformid', $uniqueId)
            ->update($row);
    }

    public function upsertByEchartSubformId(int $uniqueId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $dbRecord = DB::table(self::TABLE_NAME)
            ->where('echart_subformid', $uniqueId)
            ->first();

        if ($dbRecord) {
            $row['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat'] ?? 'Y-m-d H:i:s');
            $row['updated_by'] = 1;
            $this->update($uniqueId, $row);
            $this->supervisoryVisitsRepository->updateSupVisitNextDateFromDaas($row, 'Update');

            return;
        }
        $row['created_at'] = date_format(date_create(), config('constant')['longdatetimeformat'] ?? 'Y-m-d H:i:s');
        $this->create($row);
        $this->supervisoryVisitsRepository->updateSupVisitNextDateFromDaas($row, 'Create');
    }

    public function deleteByEchartSubformId(int $uniqueId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('echart_subformid', $uniqueId)
            ->delete();
    }
}
