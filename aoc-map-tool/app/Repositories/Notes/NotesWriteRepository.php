<?php

namespace App\Repositories\Notes;

use App\DTO\NotesDTO;
use App\Notes;

class NotesWriteRepository
{
    public function __construct(private Notes $notes)
    {
    }

    public function create(NotesDTO $notesDto): void
    {
        $this->notes->query()->create([
            'entity' => $notesDto->entity,
            'entity_id' => $notesDto->entity_id,
            'type' => $notesDto->type,
            'notes' => $notesDto->notes,
            'completed_at' => $notesDto->completed_at,
        ]);
    }
}
