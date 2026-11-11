<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerOutcomeDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerClosingReasonEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use Illuminate\Validation\Rule;

/**
 * An Outcome submission of the "Hospitalization Record" modal — every transition the tracker's lifecycle
 * supports: Open -> In Progress -> Discharged -> Ready for Review -> Ready for Next Steps -> Completed,
 * plus Closed. Closed with the "Transfer" reason also carries the new hospitalization's Current
 * Hospitalization Info fields, under `fields` (see HospitalizationTrackerOutcomeService::transfer()).
 *
 * Also covers the "ED Visit" outcome workflow (PD-4069), which shares this same endpoint/DTO: an ED
 * tracker's accepted closing reasons differ from Hospitalization's ("Hospitalization" instead of "ED
 * Visit"), so the route's `trackerId` is resolved to its encounter type to pick the right set.
 */
class UpdateHospitalizationTrackerOutcomeRequest extends CoreRequest
{
    private const ACCEPTED_OUTCOMES = [
        HospitalizationTrackerStatusEnum::IN_PROGRESS,
        HospitalizationTrackerStatusEnum::DISCHARGED,
        HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
        HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS,
        HospitalizationTrackerStatusEnum::COMPLETED,
        HospitalizationTrackerStatusEnum::CLOSED,
    ];

    private const ACCEPTED_CLOSING_REASONS_BY_ENCOUNTER_TYPE = [
        EncounterTypeEnum::IP->value => [
            HospitalizationTrackerClosingReasonEnum::ED_VISIT,
            HospitalizationTrackerClosingReasonEnum::OPENED_BY_MISTAKE,
            HospitalizationTrackerClosingReasonEnum::TRANSFERRED,
        ],
        EncounterTypeEnum::ED->value => [
            HospitalizationTrackerClosingReasonEnum::HOSPITALIZATION,
            HospitalizationTrackerClosingReasonEnum::OPENED_BY_MISTAKE,
        ],
    ];

    /** Outcomes that reassign the tracker and carry an optional free-text note, besides Discharge/Closed. */
    private const OUTCOMES_REQUIRING_ASSIGNEE_AND_NOTES = [
        HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
        HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS,
        HospitalizationTrackerStatusEnum::COMPLETED,
    ];

    public function __construct(
        private readonly HospitalizationReadRepository $readRepository,
    ) {
        parent::__construct();
    }

    public function rules(): array
    {
        $outcome = $this->input('outcome');

        // The accepted-reasons lookup resolves the tracker's own encounter type, so it's only worth the
        // extra query on an actual Closed submission — every other outcome never sends `reason` at all.
        $acceptedReasons = $outcome === HospitalizationTrackerStatusEnum::CLOSED->value
            ? $this->acceptedClosingReasons()
            : HospitalizationTrackerClosingReasonEnum::cases();

        $rules = [
            'outcome' => ['required', Rule::in(array_column(self::ACCEPTED_OUTCOMES, 'value'))],
            'reason' => [
                Rule::requiredIf($outcome === HospitalizationTrackerStatusEnum::CLOSED->value),
                Rule::in(array_column($acceptedReasons, 'value')),
            ],
        ];

        if ($outcome === HospitalizationTrackerStatusEnum::DISCHARGED->value) {
            return $rules + $this->dischargeFieldRules();
        }

        if (in_array($outcome, array_column(self::OUTCOMES_REQUIRING_ASSIGNEE_AND_NOTES, 'value'), true)) {
            return $rules + [
                'assignee_id' => ['required', 'integer', Rule::exists('users', 'id')],
                'notes' => ['nullable', 'string'],
            ];
        }

        // Closure notes (PD-4069 AC11/AC13): optional free-text, same shape as the discharge-lifecycle
        // outcomes above. Hospitalization's own Close flow has never sent this field, so it stays
        // unaffected — `notes` simply has nothing to validate when it isn't sent.
        if ($outcome === HospitalizationTrackerStatusEnum::CLOSED->value) {
            // Transfer rules first: `+` keeps the left-hand key, and a transfer makes `notes` required.
            return $this->transferFieldRules() + $rules + ['notes' => ['nullable', 'string']];
        }

        return $rules;
    }

    public function toDto(): HospitalizationTrackerOutcomeDTO
    {
        $outcome = HospitalizationTrackerStatusEnum::from($this->input('outcome'));

        return new HospitalizationTrackerOutcomeDTO(
            outcome: $outcome,
            reason: $this->filled('reason') ? HospitalizationTrackerClosingReasonEnum::from($this->input('reason')) : null,
            fields: $outcome === HospitalizationTrackerStatusEnum::DISCHARGED || $this->isTransfer()
                ? HospitalizationTrackerFieldsDTO::cast($this->input('fields', []))
                : null,
            assigneeId: $this->filled('assignee_id') ? (int) $this->input('assignee_id') : null,
            notes: $this->filled('notes') ? (string) $this->input('notes') : null,
        );
    }

    /**
     * The tracker this submission targets may be either encounter type (same table/endpoint, see
     * HospitalizationTrackerOutcomeService), and each has its own Close reason: "ED Visit" for
     * Hospitalization, "Hospitalization" for ED. Defaults to Hospitalization's set when the tracker
     * can't be resolved (unknown id) — the controller 404s on that case before the reason ever matters.
     *
     * @return HospitalizationTrackerClosingReasonEnum[]
     */
    private function acceptedClosingReasons(): array
    {
        $encounterType = $this->readRepository->findEncounterType((int) $this->route('trackerId'));

        return self::ACCEPTED_CLOSING_REASONS_BY_ENCOUNTER_TYPE[$encounterType?->value]
            ?? self::ACCEPTED_CLOSING_REASONS_BY_ENCOUNTER_TYPE[EncounterTypeEnum::IP->value];
    }

    private function isTransfer(): bool
    {
        return HospitalizationTrackerOutcomeDTO::isTransfer(
            HospitalizationTrackerStatusEnum::tryFrom((string) $this->input('outcome')),
            HospitalizationTrackerClosingReasonEnum::tryFrom((string) $this->input('reason')),
        );
    }

    /**
     * The Current Hospitalization Info of the hospitalization the patient is transferred to — see
     * HospitalizationTrackerFieldEnum::transferFields()/requiredForTransferFields() — plus the Transfer
     * Reason, which arrives as `notes` and takes the place of Admission Reason.
     */
    private function transferFieldRules(): array
    {
        if (!$this->isTransfer()) {
            return [];
        }

        $rules = ['notes' => ['required', 'string', 'max:500']];
        $required = HospitalizationTrackerFieldEnum::requiredForTransferFields();

        foreach (HospitalizationTrackerFieldEnum::transferFields() as $field) {
            $presence = in_array($field, $required, true) ? ['required'] : ['sometimes', 'nullable'];
            $rules["fields.{$field->value}"] = array_merge($presence, $field->valueRules());
        }

        return $rules;
    }

    /** The discharge-time fields, submitted under `fields` alongside the transition itself. */
    private function dischargeFieldRules(): array
    {
        $rules = ['notes' => ['nullable', 'string']];
        $required = HospitalizationTrackerFieldEnum::requiredForDischargeFields();

        foreach (HospitalizationTrackerFieldEnum::dischargeFields() as $field) {
            $presence = in_array($field, $required, true) ? ['required'] : ['sometimes', 'nullable'];
            $rules["fields.{$field->value}"] = array_merge($presence, $field->valueRules());
        }

        // Only relevant while `first_billable_shift_completed` is on — matches
        // UpdateCurrentHospitalizationInfoRequest's handling of the same pair.
        $rules['fields.' . HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_AT->value] = [
            'required_if:fields.' . HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_COMPLETED->value . ',1',
            'nullable',
            ...HospitalizationTrackerFieldEnum::FIRST_BILLABLE_SHIFT_AT->valueRules(),
        ];

        return $rules;
    }
}
