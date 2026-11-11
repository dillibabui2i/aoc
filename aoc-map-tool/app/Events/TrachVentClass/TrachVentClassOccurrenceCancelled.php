<?php

namespace App\Events\TrachVentClass;

use App\TrachVentClassOccurrence;

final readonly class TrachVentClassOccurrenceCancelled
{
    /**
     * @param  array<int, int>  $enrollerUserIds  users who enrolled clinicians into the cancelled occurrence
     */
    public function __construct(
        public TrachVentClassOccurrence $occurrence,
        public array $enrollerUserIds,
    ) {
    }
}
