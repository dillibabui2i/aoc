<?php

namespace App\PrivateAPI\Http\Requests\Trackers;

use App\MssqlFacilities;
use App\MssqlRecruitertracker;
use App\PrivateAPI\Rules\IpeaBranchIsResolvable;
use App\PrivateAPI\Rules\IpeaCannotBeScheduledViaPatch;
use App\PrivateAPI\Rules\ScheduleBelongsToTracker;
use App\PrivateAPI\Rules\ScheduleIsReschedulable;
use App\PrivateAPI\Rules\ScheduleMatchesType;
use App\PrivateAPI\Rules\ScheduleTypeIsNotScheduled;
use App\PrivateAPI\Rules\ScheduleTypeIsScheduled;
use App\RecruitingSchedule;
use App\Repositories\MssqlFacilities\MssqlFacilitiesRepository;
use App\Repositories\MssqlRecruiterTracker\MssqlRecruiterTrackerReadRepository;
use App\Repositories\RecruitingSchedule\RecruitingScheduleReadRepository;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTrackerRequest extends AbstractTrackerRequest
{
    /**
     * External scheduleType => [legacy schedule_type string, date/time/branch field prefix].
     * Unlike Create, PATCH's scheduleType values match the legacy strings verbatim
     * (the "Training" shorthand only exists on the POST/scheduleRequested contract).
     */
    private const SCHEDULE_TYPES = [
        'IPEA' => ['legacy' => 'IPEA', 'prefix' => 'ipea', 'typeId' => 1],
        'CCR' => ['legacy' => 'CCR', 'prefix' => 'ccr', 'typeId' => 2],
        'PEPI' => ['legacy' => 'PEPI', 'prefix' => 'pepi', 'typeId' => 3],
        'WEPI' => ['legacy' => 'WEPI', 'prefix' => 'wepi', 'typeId' => 4],
        'Training Shift' => ['legacy' => 'Training Shift', 'prefix' => 'trainingShift', 'typeId' => 5],
        'Candidate Start' => ['legacy' => 'Candidate Start', 'prefix' => 'candidateStart', 'typeId' => 6],
    ];

    /**
     * "Scheduled" was dropped from the ticket's tables: it has zero backend handling anywhere
     * in saveScheduleNext (grepped for `$request->outcome == 'Scheduled'` - no matches), and the
     * ticket itself marks it "Status marker only" - it's the derived step status shown back in
     * responses, not a value the caller submits. "Rescheduled" stays: it has a real branch
     * (Dashboard.php:3604). "Completed"/"Clinician Declined the Agency"/"Inactivate Tracker" stay
     * too - each has a real, unguarded string-equality branch in saveScheduleNext, even where the
     * legacy UI's own dropdown doesn't expose them for a given bucket (e.g. "Inactivate Tracker"
     * and "Clinician Declined the Agency" are only UI-reachable via other buckets/statuses, but
     * the backend branches match on the literal string regardless of how it got there).
     */
    private const OUTCOMES_BY_TYPE = [
        'IPEA' => [
            'Attempt 1',
            'Attempt 2',
            'Attempt 3',
            'NC/NS',
            'Rescheduled',
            'Completed',
            'Cancelled',
            'Clinician Declined the Agency',
            'Inactivate Tracker',
        ],
        'PEPI' => [
            'Attempt 1',
            'Attempt 2',
            'Attempt 3',
            'NC/NS',
            'Rescheduled',
            'Completed',
            'Cancelled',
            'Nurse Declined',
            'Inactivate Tracker',
        ],
        'WEPI' => [
            'Attempt 1',
            'Attempt 2',
            'Attempt 3',
            'NC/NS',
            'Rescheduled',
            'Completed',
            'Cancelled',
            'Nurse Declined',
            'Clinician Declined the Agency',
            'Inactivate Tracker',
        ],
        'CCR' => [
            'Pending Candidate Attempt 1',
            'Pending Candidate Attempt 2',
            'Pending Candidate Attempt 3',
            'Rescheduled',
            'Completed',
            'Cancelled',
            'Inactivate Tracker',
        ],
        'Training Shift' => ['NC/NS', 'Rescheduled', 'Completed', 'Cancelled', 'Inactivate Tracker'],
        'Candidate Start' => ['Client Match', 'Cancelled', 'Clinician Declined the Agency', 'Inactivate Tracker'],
    ];
    private const ATTEMPT_OUTCOMES = [
        'Attempt 1',
        'Attempt 2',
        'Attempt 3',
        'NC/NS',
        'Pending Candidate Attempt 1',
        'Pending Candidate Attempt 2',
        'Pending Candidate Attempt 3',
    ];
    /**
     * Outcomes that don't close out a step - anything else in OUTCOMES_BY_TYPE is terminal
     * and blocks a reschedule of that step.
     */
    private const NON_FINAL_OUTCOMES = [
        'Attempt 1',
        'Attempt 2',
        'Attempt 3',
        'NC/NS',
        'Rescheduled',
        'Pending Candidate Attempt 1',
        'Pending Candidate Attempt 2',
        'Pending Candidate Attempt 3',
    ];
    /**
     * cancelledReason is only meaningful (and only translated) for scheduleType = IPEA.
     */
    private const CANCELLED_REASON_MAP = [
        'Candidate chose another opportunity' => 'better_offer',
        'Nurse decided not to work for AOC' => 'not_work',
        'Change to Active Coverage' => 'change_prn',
        'Placed on case by different recruiter' => 'different_recruiter',
        'Ineligible for hire/rehire' => 'ineligible_for_hire',
    ];

    private MssqlRecruiterTrackerReadRepository $trackersRepository;
    private MssqlFacilitiesRepository $facilitiesRepository;
    private RecruitingScheduleReadRepository $schedulesRepository;
    private ?MssqlRecruitertracker $tracker = null;
    private ?RecruitingSchedule $reschedule = null;
    private ?int $rescheduleIpeaLocationId = null;
    private bool $facilityResolved = false;
    private ?MssqlFacilities $facility = null;

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pk' => $this->route('id'),
        ]);
    }

    public function rules(
        MssqlRecruiterTrackerReadRepository $trackersRepository,
        MssqlFacilitiesRepository $facilitiesRepository,
        RecruitingScheduleReadRepository $schedulesRepository,
    ): array {
        $this->trackersRepository = $trackersRepository;
        $this->facilitiesRepository = $facilitiesRepository;
        $this->schedulesRepository = $schedulesRepository;

        $rules = [
            'pk' => ['required', 'integer', Rule::exists('mssql_recruitertracker', 'id')->where('is_deleted', '0')],
        ];

        if (!$this->has('scheduleType')) {
            // PATCH mode 4: metadata-only update.
            return array_merge($rules, $this->metadataRules());
        }

        $rules['scheduleType'] = ['required', 'string', Rule::in(array_keys(self::SCHEDULE_TYPES))];

        $scheduleType = $this->input('scheduleType');
        $prefix = self::SCHEDULE_TYPES[$scheduleType]['prefix'] ?? null;

        if ($this->has('outcome')) {
            // PATCH mode 3: submit outcome for an existing meeting.
            $allowedOutcomes = self::OUTCOMES_BY_TYPE[$scheduleType] ?? [];

            $rules['outcome'] = ['required', 'string', Rule::in($allowedOutcomes)];
            $rules['cancelledReason'] = [
                Rule::requiredIf($this->input('outcome') === 'Cancelled' && $scheduleType === 'IPEA'),
                'nullable',
                Rule::in(array_keys(self::CANCELLED_REASON_MAP)),
            ];

            return array_merge($rules, $this->metadataRules());
        }

        // PATCH mode 1 (schedule next) or mode 2 (reschedule) - both need date/time for the type.
        if ($prefix) {
            $rules[$prefix . 'Date'] = ['required', 'date'];
            $rules[$prefix . 'Time'] = ['required', 'date_format:H:i'];

            if ($scheduleType === 'IPEA') {
                $rules[$prefix . 'Branch'] = ['sometimes', 'nullable', 'integer', Rule::exists('branch_locations', 'id')];
            }
        }

        $rules['scheduleId'] = ['sometimes', 'nullable', 'integer'];
        $rules['appointmentId'] = ['sometimes', 'nullable', 'integer'];

        return array_merge($rules, $this->metadataRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty() || !$this->has('scheduleType')) {
                return;
            }

            $scheduleType = $this->input('scheduleType');
            $outcome = $this->input('outcome');

            // Mirrors the legacy automation's own precedence: its outcome branches key off
            // $request->outcome and the tracker's current bucket only, and never read
            // appointment_date/time, so any scheduling fields sent alongside outcome (as the UI
            // itself does - it resubmits the tracker's existing date/time unchanged whenever an
            // outcome is selected) are inert, not conflicting. Rejecting them would diverge from
            // that established behavior.
            if ($outcome !== null) {
                $this->validateOutcomeSubmission($validator, $scheduleType);

                return;
            }

            $prefix = self::SCHEDULE_TYPES[$scheduleType]['prefix'] ?? null;

            if ($prefix && $this->has($prefix . 'Date')) {
                $this->validateScheduling($validator, $scheduleType);
            }
        });
    }

    private function validateOutcomeSubmission(Validator $validator, string $scheduleType): void
    {
        $typeId = self::SCHEDULE_TYPES[$scheduleType]['typeId'];

        $rule = new ScheduleTypeIsScheduled($this->schedulesRepository, $this->input('pk'), $typeId);

        if (!$rule->passes('scheduleType', null)) {
            $validator->errors()->add('_not_found', $rule->message());
        }
    }

    private function validateScheduling(Validator $validator, string $scheduleType): void
    {
        $typeId = self::SCHEDULE_TYPES[$scheduleType]['typeId'];
        $scheduleId = $this->input('scheduleId') ?? $this->input('appointmentId');

        if (!$scheduleId) {
            $this->validateFreshSchedule($validator, $scheduleType, $typeId);

            return;
        }

        $this->validateReschedule($validator, $scheduleType, $typeId, (int) $scheduleId);
    }

    /**
     * "schedule next step" intent - no scheduleId means the caller wants a fresh row for this
     * type.
     */
    private function validateFreshSchedule(Validator $validator, string $scheduleType, int $typeId): void
    {
        $ipeaRule = new IpeaCannotBeScheduledViaPatch($scheduleType);

        if (!$ipeaRule->passes('scheduleType', null)) {
            $validator->errors()->add('_conflict', $ipeaRule->message());

            return;
        }

        $notYetScheduledRule = new ScheduleTypeIsNotScheduled($this->schedulesRepository, $this->input('pk'), $typeId);

        if (!$notYetScheduledRule->passes('scheduleId', null)) {
            $validator->errors()->add('scheduleId', $notYetScheduledRule->message());
        }
    }

    /**
     * "reschedule" intent - resolves the target row once and, if every check passes, caches it
     * on $this->reschedule for passedValidation() to reuse when building the legacy payload.
     */
    private function validateReschedule(Validator $validator, string $scheduleType, int $typeId, int $scheduleId): void
    {
        $schedule = $this->schedulesRepository->findById($scheduleId);

        if (!$schedule) {
            $validator->errors()->add('_not_found', 'The requested schedule was not found.');

            return;
        }

        $ownershipRule = new ScheduleBelongsToTracker($schedule, $this->input('pk'));

        if (!$ownershipRule->passes('scheduleId', null)) {
            $validator->errors()->add('scheduleId', $ownershipRule->message());

            return;
        }

        $typeRule = new ScheduleMatchesType($schedule, $typeId);

        if (!$typeRule->passes('scheduleType', null)) {
            $validator->errors()->add('scheduleType', $typeRule->message());

            return;
        }

        $reschedulableRule = new ScheduleIsReschedulable($schedule, self::NON_FINAL_OUTCOMES);

        if (!$reschedulableRule->passes('outcome', null)) {
            $validator->errors()->add('_conflict', $reschedulableRule->message());

            return;
        }

        if ($scheduleType === 'IPEA') {
            // Rescheduling an IPEA rewrites its location from the request and the First
            // Advantage branch dereferences that branch row unconditionally
            // (Dashboard.php:4709), so refuse the call instead of wiping the location and then
            // 500-ing on an already-updated row.
            $branchRule = new IpeaBranchIsResolvable(
                true,
                $schedule->ipea_location_id ?: $this->facility()?->branchid,
                'ipeaBranch is required: the IPEA has no branch location and none is linked to the facility.',
            );

            if (!$branchRule->passes('ipeaBranch', $this->input('ipeaBranch'))) {
                $validator->errors()->add('ipeaBranch', $branchRule->message());

                return;
            }

            $this->rescheduleIpeaLocationId = $branchRule->getResolvedBranchId();
        }

        $this->reschedule = $schedule;
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->mergeMetadataFields();

        // saveScheduleNext's Edit branch reads the client and clinician off the request, not off
        // the tracker it has already loaded. patient_id is the important one: without it the
        // "does this tracker still need a CCR" probe (Dashboard.php:3027) matches nothing and
        // forces needsCCR = 1, which silently rewrites "schedule a PEPI/WEPI/Training
        // Shift/Candidate Start" into "propose it, and book a CCR instead" - no row for the type
        // that was asked for, plus a CCR nobody requested. branch_location is what the schedule
        // row's aoc_facility_id is resolved from.
        $merge = [
            'mode' => 'Edit',
            'tracker_id' => $this->input('pk'),
            'patient_id' => $this->tracker()->aoc_patient_id,
            'provider_id' => $this->tracker()->aoc_provider_id,
            'candidate_id' => $this->tracker()->aoc_candidate_id,
            'branch_location' => $this->facility()->FacilityName ?? '',
            // The legacy form posts an empty schedule_type unless something is being scheduled;
            // every branch keyed on it (bucket status rewrites, the IPEA e-mails, the CCR
            // fallback) must stay out of the way of a plain outcome submission.
            'schedule_type' => '',
        ];

        if ($this->has('scheduleType')) {
            $scheduleType = $this->input('scheduleType');

            $merge = array_merge(
                $merge,
                $this->has('outcome')
                    ? $this->outcomeFields($scheduleType)
                    : $this->schedulingFields($scheduleType)
            );
        }

        $this->merge($merge);
    }

    private function outcomeFields(string $scheduleType): array
    {
        $outcome = $this->input('outcome');
        $fields = ['outcome' => $outcome];

        if (in_array($outcome, self::ATTEMPT_OUTCOMES, true)) {
            // Existing automation reads this exact (misspelled) key - do not "fix" it.
            $fields['attampted_to_contact'] = 1;
        }

        if ($outcome === 'Cancelled' && $scheduleType === 'IPEA' && $this->has('cancelledReason')) {
            $fields['cancelled_reason'] = self::CANCELLED_REASON_MAP[$this->input('cancelledReason')];
        }

        return $fields;
    }

    private function schedulingFields(string $scheduleType): array
    {
        $prefix = self::SCHEDULE_TYPES[$scheduleType]['prefix'];

        $fields = [
            'schedule_type' => self::SCHEDULE_TYPES[$scheduleType]['legacy'],
            'appointment_date' => $this->input($prefix . 'Date'),
            'appointment_time' => $this->input($prefix . 'Time'),
        ];

        if ($this->reschedule) {
            // The legacy UI submits a reschedule as the "Rescheduled" outcome, and that is what
            // marks the step being replaced and picks the "your interview has been rescheduled"
            // e-mail over the "New IPEA Created" one (Dashboard.php:4471/4490).
            $fields['outcome'] = 'Rescheduled';
        }

        if ($scheduleType === 'IPEA') {
            // Only reachable via a reschedule (a fresh IPEA is always rejected by
            // IpeaCannotBeScheduledViaPatch), so the branch was already resolved and validated
            // in validateReschedule().
            $fields['ipea_location_id'] = $this->rescheduleIpeaLocationId;
            // Assigned from the request on every IPEA save, so they have to be carried over.
            $fields['is_rehire'] = $this->tracker()->is_rehire;
            $fields['new_grad'] = $this->tracker()->new_grad;
            $fields['why_nurse_chosen'] = $this->tracker()->why_nurse_chosen;
        }

        return array_merge($fields, $this->preservedRateFields());
    }

    /**
     * pay_rate, gp and gp_approved_by are written onto the schedule row from the request on every
     * save, so whatever the caller omits has to be carried over instead of being zeroed out.
     */
    private function preservedRateFields(): array
    {
        $fields = [];

        if (!$this->has('agreedPayRate')) {
            $fields['pay_rate'] = $this->reschedule?->pay_rate ?? $this->tracker()->Agreed_Pay_Rate;
        }

        if (!$this->has('gp')) {
            // Stored as a fraction, submitted as a percentage: the legacy form posts "36" for 0.36.
            $storedGp = $this->reschedule?->gp ?? $this->tracker()->GP;
            $fields['gp'] = $storedGp === null ? '' : (float) $storedGp * 100;
        }

        if (!$this->has('approvedBy')) {
            $fields['gp_approved_by'] = $this->reschedule?->gp_approved_by ?? $this->tracker()->Approved_By;
        }

        return $fields;
    }

    private function tracker(): MssqlRecruitertracker
    {
        return $this->tracker ??= $this->trackersRepository->getById((int) $this->input('pk'));
    }

    private function facility(): ?MssqlFacilities
    {
        if (!$this->facilityResolved) {
            $this->facilityResolved = true;
            $this->facility = $this->facilitiesRepository->getById((int) $this->tracker()->aoc_facility_id);
        }

        return $this->facility;
    }
}
