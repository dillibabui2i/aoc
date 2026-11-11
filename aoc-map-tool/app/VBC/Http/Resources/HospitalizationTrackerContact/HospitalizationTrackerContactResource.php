<?php

namespace App\VBC\Http\Resources\HospitalizationTrackerContact;

use App\VBC\Models\HospitalizationTrackerContact\HospitalizationTrackerContactModel;
use Illuminate\Http\Resources\Json\JsonResource;

class HospitalizationTrackerContactResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerContactModel $contact */
        $contact = $this->resource;

        return [
            'id' => $contact->id,
            'contactName' => $contact->contact_name,
            // The label, for the table/read-only view; the modal's edit form needs the id back to
            // pre-select the right `<option>` in the Relationship dropdown (see ContactDetailsModal.vue).
            'relationship' => $contact->type->name,
            'contactTypeId' => $contact->contact_type_id,
            // Bare 10 digits, exactly as stored — display formatting ("+1 (XXX) XXX-XXXX") is a
            // frontend concern, not the API's; see formatUsPhoneDisplay() in hospitalizationTracker.service.js.
            'phoneNumber' => $contact->phone_number,
            'email' => $contact->email,
            'contactDate' => $contact->contact_date->format(config('vbc.date_time_format')),
            'contactReceivedBy' => $contact->receivedBy->name,
            'contactNotes' => $contact->contact_notes,
        ];
    }
}
