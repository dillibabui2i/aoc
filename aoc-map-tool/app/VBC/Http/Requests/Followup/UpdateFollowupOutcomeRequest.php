<?php

namespace App\VBC\Http\Requests\Followup;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Followup\FollowupOutcomeDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use Illuminate\Validation\Rule;

/**
 * A Follow-up Outcome submission (AC7-AC19). Required fields per outcome are per design.md Decision 8 of
 * the (frontend-only) hospitalization-follow-ups story this backend now backs: Scheduled needs a date and
 * an assignee; Completed needs Status/Follow-Ups-Updates/Needs (Additional Notes optional); Attempted
 * needs Attempt Notes only; Canceled needs nothing. Completed/Attempted additionally require the next
 * follow-up's date and assignee when Next Follow-up Due is toggled on (AC26/AC28).
 */
class UpdateFollowupOutcomeRequest extends CoreRequest
{
    private const ACCEPTED_OUTCOMES = [
        HospitalizationTrackerFollowupStatusEnum::SCHEDULED,
        HospitalizationTrackerFollowupStatusEnum::COMPLETED,
        HospitalizationTrackerFollowupStatusEnum::ATTEMPTED,
        HospitalizationTrackerFollowupStatusEnum::CANCELED,
    ];

    public function rules(): array
    {
        $outcome = $this->input('outcome');

        $rules = [
            'outcome' => ['required', Rule::in(array_column(self::ACCEPTED_OUTCOMES, 'value'))],
        ];

        if ($outcome === HospitalizationTrackerFollowupStatusEnum::SCHEDULED->value) {
            return $rules + [
                'scheduled_at' => ['required', 'date'],
                'assigned_to_id' => ['required', 'integer', Rule::exists('users', 'id')],
            ];
        }

        if ($outcome === HospitalizationTrackerFollowupStatusEnum::COMPLETED->value) {
            return $rules + [
                'status_note' => ['required', 'string'],
                'follow_ups_updates' => ['required', 'string'],
                'needs' => ['required', 'string'],
                'notes' => ['sometimes', 'nullable', 'string'],
            ] + $this->nextFollowUpRules();
        }

        if ($outcome === HospitalizationTrackerFollowupStatusEnum::ATTEMPTED->value) {
            return $rules + [
                'notes' => ['required', 'string'],
            ] + $this->nextFollowUpRules();
        }

        return $rules;
    }

    public function toDto(): FollowupOutcomeDTO
    {
        $outcome = HospitalizationTrackerFollowupStatusEnum::from($this->input('outcome'));
        $nextFollowUpDue = $this->boolean('next_follow_up_due');

        return new FollowupOutcomeDTO(
            outcome: $outcome,
            scheduledAt: $this->filled('scheduled_at') ? $this->date('scheduled_at') : null,
            assignedToId: $this->filled('assigned_to_id') ? (int) $this->input('assigned_to_id') : null,
            statusNote: $this->filled('status_note') ? (string) $this->input('status_note') : null,
            followUpsUpdates: $this->filled('follow_ups_updates') ? (string) $this->input('follow_ups_updates') : null,
            needs: $this->filled('needs') ? (string) $this->input('needs') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
            nextFollowUpDue: $nextFollowUpDue,
            nextFollowUpDueDate: $nextFollowUpDue && $this->filled('next_follow_up_due_date')
                ? $this->date('next_follow_up_due_date')
                : null,
            nextAssignedToId: $nextFollowUpDue && $this->filled('next_assigned_to_id')
                ? (int) $this->input('next_assigned_to_id')
                : null,
        );
    }

    /** Only required once Next Follow-up Due is toggled on (AC11/AC15, AC26/AC28). */
    private function nextFollowUpRules(): array
    {
        $nextFollowUpDue = $this->boolean('next_follow_up_due');

        return [
            'next_follow_up_due' => ['sometimes', 'boolean'],
            'next_follow_up_due_date' => [Rule::requiredIf($nextFollowUpDue), 'nullable', 'date'],
            'next_assigned_to_id' => [Rule::requiredIf($nextFollowUpDue), 'nullable', 'integer', Rule::exists('users', 'id')],
        ];
    }
}
