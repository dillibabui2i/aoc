<?php

namespace App\DTO;

use Carbon\Carbon;

readonly class NotesDTO
{
    public function __construct(
        public readonly ?string $entity = null,
        public readonly ?string $entity_id = null,
        public readonly ?string $type = null,
        public readonly ?string $notes = null,
        public readonly ?Carbon $completed_at = null
    ) {}
}
