<?php

namespace App\VBC\Http\Requests\HospitalizationTrackerContact;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerContactDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Rules\UsPhoneNumber;
use Illuminate\Validation\Rule;

class StoreHospitalizationTrackerContactRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            // Which viewer is asking (IP when omitted) — see HospitalizationTrackerContactController::findTracker().
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
            'contact_type_id' => ['required', 'integer', 'exists:contact_types,id'],
            'contact_name' => ['required', 'string', 'max:255'],
            // At least one of Phone Number / E-mail is required — see `attributes()` for the shared
            // "either" wording both fields' required-error message uses.
            'phone_number' => ['required_without:email', 'nullable', 'string', 'max:255', new UsPhoneNumber()],
            'email' => ['required_without:phone_number', 'nullable', 'email', 'max:255'],
            'contact_notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'contact_type_id' => 'Relationship',
            'contact_name' => 'Contact Name',
            'phone_number' => 'Phone Number or E-mail',
            'email' => 'Phone Number or E-mail',
            'contact_notes' => 'Contact Notes',
        ];
    }

    // "Contact received by" and "Contact Date" aren't fields on this request: both are always the
    // current user/time, set by HospitalizationTrackerContactService::create() — never client input.
    public function toDto(): HospitalizationTrackerContactDTO
    {
        return new HospitalizationTrackerContactDTO(
            contactTypeId: (int) $this->input('contact_type_id'),
            contactName: $this->input('contact_name'),
            // Stored as bare digits, not the frontend's `(555) 123-4567` mask — displayed as E.164
            // (+1XXXXXXXXXX) by HospitalizationTrackerContactResource.
            phoneNumber: UsPhoneNumber::digitsOnly($this->input('phone_number')),
            email: $this->input('email'),
            contactNotes: $this->filled('contact_notes') ? $this->input('contact_notes') : null,
        );
    }
}
