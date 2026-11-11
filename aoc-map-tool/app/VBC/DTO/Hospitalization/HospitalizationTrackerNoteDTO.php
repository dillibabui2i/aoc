<?php

namespace App\VBC\DTO\Hospitalization;

/** A single note submitted through the "Create Notes" modal (AC5-AC10). */
final readonly class HospitalizationTrackerNoteDTO
{
    public function __construct(public string $notes)
    {
    }
}
