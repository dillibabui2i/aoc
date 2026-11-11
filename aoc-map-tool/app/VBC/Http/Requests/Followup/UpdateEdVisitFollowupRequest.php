<?php

namespace App\VBC\Http\Requests\Followup;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Followup\EdVisitFollowupOutcomeDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use Illuminate\Validation\Rule;

/** ED Visit Follow-up outcome (PD-4073): Completed / First / Second / Third Attempt, notes always required (AC10). */
class UpdateEdVisitFollowupRequest extends CoreRequest
{
    private const ACCEPTED_OUTCOMES = [
        HospitalizationTrackerFollowupStatusEnum::COMPLETED,
        HospitalizationTrackerFollowupStatusEnum::FIRST_ATTEMPT,
        HospitalizationTrackerFollowupStatusEnum::SECOND_ATTEMPT,
        HospitalizationTrackerFollowupStatusEnum::THIRD_ATTEMPT,
    ];

    public function rules(): array
    {
        return [
            'outcome' => ['required', Rule::in(array_column(self::ACCEPTED_OUTCOMES, 'value'))],
            'notes' => ['required', 'string'],
        ];
    }

    public function toDto(): EdVisitFollowupOutcomeDTO
    {
        return new EdVisitFollowupOutcomeDTO(
            outcome: HospitalizationTrackerFollowupStatusEnum::from($this->input('outcome')),
            notes: (string) $this->input('notes'),
        );
    }
}
