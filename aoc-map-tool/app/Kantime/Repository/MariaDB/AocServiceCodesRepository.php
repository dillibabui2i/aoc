<?php

namespace App\Kantime\Repository\MariaDB;

use App\Kantime\Services\KantimeSyncServiceInterface;
use App\Kantime\Services\ServiceCodesSyncService;
use Illuminate\Support\Facades\DB;

final class AocServiceCodesRepository
{
    const TABLE_NAME = 'service_codes_daas';

    public function postSyncUpdate(KantimeSyncServiceInterface $process, $upsertedCount): void
    {
        if (!$process instanceof ServiceCodesSyncService || $upsertedCount === 0) {
            logger()->info("ServiceCodesSyncService: Post sync update bounced. Nothing to update.");
            return;
        }

        $tableName = self::TABLE_NAME;
        DB::statement("update {$tableName}
                        left join lob on lob.LOBName =
                            case
                                when service_codes_daas.type = 'SN' then
                                    case
                                        when service_codes_daas.name like '%SV%' then 'Skilled Visits'
                                        when service_codes_daas.name like '%CNA%' then 'CNA'
                                        when service_codes_daas.name like '%IHSS%' then 'IHSS'
                                        else 'PDN'
                                    end
                                when service_codes_daas.type = 'AIDE' then 'Attendant Care'
                                else service_codes_daas.type
                            end
                        set service_codes_daas.lobid = lob.LOBID;");
    }

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $uniqueId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('service_id', $uniqueId)
            ->update($row);
    }

    public function upsertByServiceId(int $uniqueId, array $row): void
    {
        $dbRecord = DB::table(self::TABLE_NAME)
            ->where('service_id', $uniqueId)
            ->first();

        if ($dbRecord) {
            $this->update($uniqueId, $row);
            return;
        }

        $this->create($row);
    }
}
