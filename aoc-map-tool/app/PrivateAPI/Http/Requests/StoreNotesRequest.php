<?php

namespace App\PrivateAPI\Http\Requests;

class StoreNotesRequest extends ApiCoreRequest
{
    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'in:tracker,candidate,clinician,nthAccount'],
            'entity_id' => ['required', 'numeric'],
            'type' => ['nullable', 'string'],
            'mention' => ['nullable', 'string'],
            'notes_text' => ['nullable', 'string'],
            'title' => ['nullable', 'string'],
        ];
    }

}
