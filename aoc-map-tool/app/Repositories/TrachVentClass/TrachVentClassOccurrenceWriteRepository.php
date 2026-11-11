<?php

namespace App\Repositories\TrachVentClass;

use App\TrachVentClassOccurrence;

class TrachVentClassOccurrenceWriteRepository
{
    public function __construct(
        private TrachVentClassOccurrence $trachVentClassOccurrence,
    ) {
    }

    /**
     * @param  array<int, int>  $occurrenceIds
     */
    public function deleteByIds(array $occurrenceIds): void
    {
        if ($occurrenceIds === []) {
            return;
        }

        $this->trachVentClassOccurrence->newQuery()
            ->whereIn('id', $occurrenceIds)
            ->delete();
    }
}
