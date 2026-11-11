<?php

namespace App\VBC\Http\Resources\Hospitalization;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * One Activity Log entry of a tracker. The underlying rows come from the shared `notes` table via
 * App\Helpers\ActivityNotes, which returns plain objects joined with the author's name — not one of this
 * module's models, hence the loose property access.
 */
class HospitalizationTrackerNoteResource extends JsonResource
{
    public function toArray($request): array
    {
        $note = $this->resource;
        $createdAt = empty($note->created_at) ? null : Carbon::parse($note->created_at);

        return [
            'id' => $note->id,
            'notes' => $note->notes,
            'author_name' => $note->name,
            'created_at' => $createdAt?->format(config('vbc.date_time_format')),
            'commented_at' => $createdAt?->format(config('vbc.activity_log_date_time_format')),
        ];
    }
}
