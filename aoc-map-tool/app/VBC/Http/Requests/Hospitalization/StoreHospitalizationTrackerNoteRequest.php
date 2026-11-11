<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerNoteDTO;

/** "Create Notes" modal submission (AC7/AC8): blank notes are rejected here, before they reach the tracker. */
class StoreHospitalizationTrackerNoteRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'notes' => ['required', 'string'],
        ];
    }

    public function toDto(): HospitalizationTrackerNoteDTO
    {
        return new HospitalizationTrackerNoteDTO((string) $this->input('notes'));
    }
}
