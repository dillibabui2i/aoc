<?php

namespace App\Kantime\Services;

interface KantimeSyncServiceInterface
{
    /**
     * Run sync for an optional date range.
     * Dates can be 'Y-m-d' strings or null (meaning default range).
     */
    public function sync(): void;
}
