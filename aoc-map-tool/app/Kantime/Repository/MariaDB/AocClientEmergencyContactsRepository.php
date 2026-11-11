<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocClientEmergencyContactsRepository
{
    private const TABLE_NAME = 'client_emergency_contacts';

    public function deleteByClientIdAndIntakeId(?int $clientId, ?int $intakeId): int
    {
        if (($clientId === null || $clientId === 0) && ($intakeId === null || $intakeId === 0)) {
            return 0;
        }

        $query = DB::table(self::TABLE_NAME);

        if ($clientId !== null && $clientId !== 0) {
            $query->where('client_id', $clientId);
        } else {
            $query->whereNull('client_id');
        }

        if ($intakeId !== null && $intakeId !== 0) {
            $query->where('intake_id', $intakeId);
        } else {
            $query->where(function ($builder): void {
                $builder->whereNull('intake_id')->orWhere('intake_id', 0);
            });
        }

        return $query->delete();
    }

    public function insert(array $newRow): void
    {
        DB::table(self::TABLE_NAME)->insert($newRow);
    }
}
