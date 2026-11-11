<?php

namespace App\Repositories\Notes;

use App\Enums\MssqlScheduleMasterBillingBucketEnum;
use App\Notes;

class NotesReadRepository
{
    public function __construct(private Notes $notes)
    {
    }

    public function getUnbilledBucketScheduleIdsWithBillingNote(array $notePatterns): array
    {
        return $this->notes->query()
            ->from('notes as n')
            ->join('mssql_schedulemaster as sm', 'sm.id', '=', 'n.entity_id')
            ->where('n.entity', 'billing')
            ->where(function ($query) use ($notePatterns) {
                foreach ($notePatterns as $pattern) {
                    $query->orWhere('n.notes', 'like', $pattern);
                }
            })
            ->where('sm.IsBilled', 0)
            ->whereIn('sm.billing_bucket_type', [
                MssqlScheduleMasterBillingBucketEnum::BILL_READY->value,
                MssqlScheduleMasterBillingBucketEnum::NOT_BILL_READY->value,
            ])
            ->distinct()
            ->pluck('sm.CGTaskID')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
