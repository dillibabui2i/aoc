<?php

namespace App\Http\Requests\Candidates;

use App\Enums\JobviteTherapyOutcomeEnum;
use App\Http\Requests\CoreRequest;
use App\Services\Offer\OfferResourcesService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Arr;

class SaveOutcomeRequest extends CoreRequest
{
    public function rules(): array
    {
        return [
            'offer_extended_files' => [
                'nullable',
                'array',
                'max:'  . OfferResourcesService::OFFER_EXTENDED_FILES_MAX_COUNT,
            ],
            'offer_extended_files' . '.*' => [
                'file',
                'mimes:' . implode(',', OfferResourcesService::OFFER_EXTENDED_FILES_MIMES),
            ],
            'date' => [
                'required_if:jobViteOutcome,' . JobviteTherapyOutcomeEnum::SCHEDULED_RECRUITER_PHONE_SCREEN->value,
            ],
            'time' => [
                'required_if:jobViteOutcome,' . JobviteTherapyOutcomeEnum::SCHEDULED_RECRUITER_PHONE_SCREEN->value,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'offer_extended_files.max' => 'You can attach up to '
                . OfferResourcesService::OFFER_EXTENDED_FILES_MAX_COUNT . ' files.',
            'offer_extended_files.*.file' => 'The attachment could not be uploaded, please try again.',
            'offer_extended_files.*.mimes' => 'Allowed attachment formats: ' . $this->getAllowedFormatsLabel() . '.',
            'date.required_if' => 'Scheduled Recruiter Phone Screen Date is required.',
            'time.required_if' => 'Scheduled Recruiter Phone Screen Time is required.',
        ];
    }

    /**
     * The size limit applies to the attachments as a whole, which no single-field rule expresses.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->hasFile('offer_extended_files')) {
                return;
            }

            $totalSize = 0;

            foreach (Arr::wrap($this->file('offer_extended_files')) as $file) {
                $totalSize += (int) $file->getSize();
            }

            if ($totalSize > OfferResourcesService::OFFER_EXTENDED_FILES_MAX_TOTAL_SIZE) {
                $validator->errors()->add('offer_extended_files', 'Total size of the attachments must not exceed '
                    . $this->getMaxTotalSizeLabel() . '.');
            }
        });
    }

    private function getAllowedFormatsLabel(): string
    {
        return implode(', ', array_map('strtoupper', OfferResourcesService::OFFER_EXTENDED_FILES_MIMES));
    }

    private function getMaxTotalSizeLabel(): string
    {
        return round(OfferResourcesService::OFFER_EXTENDED_FILES_MAX_TOTAL_SIZE / 1024 / 1024) . ' MB';
    }
}
