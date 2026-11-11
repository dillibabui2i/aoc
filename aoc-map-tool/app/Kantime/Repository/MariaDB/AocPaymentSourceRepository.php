<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;

final class AocPaymentSourceRepository
{
    private const TABLE_NAME = 'payment_source';

    public function existsByPaymentSourceId(int $paymentSourceId): bool
    {
        return DB::table(self::TABLE_NAME)
            ->where('payersource_id', $paymentSourceId)
            ->exists();
    }

    public function updateByPaymentSourceId(int $paymentSourceId, array $payload): void
    {
        DB::table(self::TABLE_NAME)
            ->where('payersource_id', $paymentSourceId)
            ->update($payload);
    }

    public function insert(array $payload): void
    {
        DB::table(self::TABLE_NAME)->insert($payload);
    }
}
