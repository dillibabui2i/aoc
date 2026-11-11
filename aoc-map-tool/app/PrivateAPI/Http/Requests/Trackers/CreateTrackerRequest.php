<?php

namespace App\PrivateAPI\Http\Requests\Trackers;

use App\MssqlFacilities;
use App\MssqlPatients;
use App\PrivateAPI\Rules\IpeaBranchIsResolvable;
use App\PrivateAPI\Rules\NoActiveTrackerExists;
use App\PrivateAPI\Rules\PatientFacilityMatches;
use App\Repositories\Candidates\CandidateReadRepository;
use App\Repositories\MssqlFacilities\MssqlFacilitiesRepository;
use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use App\Repositories\MssqlProviders\MssqlProvidersReadRepository;
use App\Repositories\MssqlRecruiterTracker\MssqlRecruiterTrackerReadRepository;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CreateTrackerRequest extends AbstractTrackerRequest
{
    /**
     * External scheduleRequested value => legacy schedule_type value.
     * "Training" is the only one that doesn't match the legacy string verbatim
     * (legacy/schedule_types table stores "Training Shift").
     */
    private const SCHEDULE_TYPES = [
        'IPEA' => 'IPEA',
        'PEPI' => 'PEPI',
        'WEPI' => 'WEPI',
        'CCR' => 'CCR',
        'Training' => 'Training Shift',
    ];

    private MssqlPatientsReadRepository $patientsRepository;
    private MssqlFacilitiesRepository $facilitiesRepository;
    private MssqlProvidersReadRepository $providersRepository;
    private CandidateReadRepository $candidatesRepository;
    private MssqlRecruiterTrackerReadRepository $trackersRepository;
    private ?MssqlPatients $patient = null;
    private ?MssqlFacilities $facility = null;
    private ?int $ipeaLocationId = null;

    public function rules(
        MssqlPatientsReadRepository $patientsRepository,
        MssqlFacilitiesRepository $facilitiesRepository,
        MssqlProvidersReadRepository $providersRepository,
        CandidateReadRepository $candidatesRepository,
        MssqlRecruiterTrackerReadRepository $trackersRepository,
    ): array {
        $this->patientsRepository = $patientsRepository;
        $this->facilitiesRepository = $facilitiesRepository;
        $this->providersRepository = $providersRepository;
        $this->candidatesRepository = $candidatesRepository;
        $this->trackersRepository = $trackersRepository;

        return array_merge([
            'patientId' => ['required', 'integer', Rule::exists('mssql_patients', 'id')],
            'providerId' => ['required_without:candidateId', 'nullable', 'integer', Rule::exists('mssql_providers', 'id')],
            'candidateId' => ['required_without:providerId', 'nullable', 'integer', Rule::exists('candidates', 'id')],
            'facilityId' => ['required', 'integer', Rule::exists('mssql_facilities', 'id')],
            'scheduleRequested' => ['required', 'string', Rule::in(array_keys(self::SCHEDULE_TYPES))],
            'appointmentDate' => ['required', 'date'],
            'appointmentTime' => ['required', 'date_format:H:i'],
            // Optional: defaults to the branch office of the client's facility (mssql_facilities.branchid).
            'ipeaBranch' => ['sometimes', 'nullable', 'integer', Rule::exists('branch_locations', 'id')],
            'isRehire' => ['sometimes', 'nullable', 'boolean'],
            'newGrad' => ['sometimes', 'nullable', 'boolean'],
        ], $this->metadataRules());
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $facilityRule = new PatientFacilityMatches(
                $this->patientsRepository,
                $this->facilitiesRepository,
                $this->input('patientId'),
            );

            if (!$facilityRule->passes('facilityId', $this->input('facilityId'))) {
                $validator->errors()->add('facilityId', $facilityRule->message());

                return;
            }

            $this->patient = $facilityRule->getPatient();
            $this->facility = $facilityRule->getFacility();

            // The legacy form refuses to submit an IPEA without a location, and saveScheduleNext
            // relies on it: it's the branch whose template/attachments the candidate's IPEA e-mail
            // is built from and the row the First Advantage branch dereferences unconditionally.
            // Fail here rather than let a null through.
            $ipeaRule = new IpeaBranchIsResolvable(
                $this->input('scheduleRequested') === 'IPEA',
                $this->facility?->branchid,
                'ipeaBranch is required for an IPEA: no branch location is linked to the facility.',
            );

            if (!$ipeaRule->passes('ipeaBranch', $this->input('ipeaBranch'))) {
                $validator->errors()->add('ipeaBranch', $ipeaRule->message());

                return;
            }

            $this->ipeaLocationId = $ipeaRule->getResolvedBranchId();

            $noActiveTracker = new NoActiveTrackerExists(
                $this->trackersRepository,
                $this->input('candidateId'),
                $this->input('providerId'),
            );

            if (!$noActiveTracker->passes('patientId', $this->input('patientId'))) {
                $validator->errors()->add('_conflict', $noActiveTracker->message());
            }
        });
    }

    protected function passedValidation(): void
    {
        parent::passedValidation();

        $this->mergeMetadataFields();

        $scheduleType = self::SCHEDULE_TYPES[$this->input('scheduleRequested')];

        $legacy = [
            'mode' => 'add',
            'patient_id' => $this->input('patientId'),
            'provider_id' => $this->input('providerId'),
            'candidate_id' => $this->input('candidateId'),
            'schedule_type' => $scheduleType,
            'appointment_date' => $this->input('appointmentDate'),
            'appointment_time' => $this->input('appointmentTime'),
            'branch_location' => $this->facility->FacilityName ?? '',
            // Same source the Edit branch uses for Payor_Source when a client is (re)assigned.
            'payer' => $this->patient->Payer ?? '',
            // calculateBonusAmount() indexes gp directly, so the key has to exist even when unsent.
            'gp' => $this->input('gp') ?? '',
            // mssql_recruitertracker.is_rehire is NOT NULL with no default, and new_grad is NOT
            // NULL despite having one (an explicit NULL in the insert still violates it) - the
            // legacy form always posts both from checkboxes, so an unset request key here is a
            // straight insert failure (this was the other half of the POST 500, alongside
            // calculateBonusAmount()'s undefined-index warnings).
            'is_rehire' => (int) (bool) $this->input('isRehire', false),
            'new_grad' => (int) (bool) $this->input('newGrad', false),
        ];

        if ($scheduleType === 'IPEA') {
            $legacy['ipea_location_id'] = $this->ipeaLocationId;
        }

        $this->merge(array_merge($legacy, $this->legacyClinicianFields()));
    }

    /**
     * saveScheduleNext's "add" branch is written against the whole legacy Add-clinician form, not
     * just the fields this endpoint exposes: MssqlRecruitertracker::calculateBonusAmount() indexes
     * branch_location/title/gp/bilingual directly (an absent key is a PHP warning, which Laravel
     * promotes to an ErrorException - that is the POST 500), Candidate/Skillset/Bilingual are
     * written from the request verbatim, and `status` picks the candidate ("new") vs. existing
     * clinician ("current") branch. The legacy UI fills all of it from the row its autocomplete
     * just looked up - candidates.title / mssql_providers.DisciplineName, see
     * Dashboard::getCandidateName() - so resolve the same values here instead of sending nulls.
     */
    private function legacyClinicianFields(): array
    {
        if (!empty($this->input('candidateId'))) {
            $candidate = $this->candidatesRepository->getCandidateByID((int) $this->input('candidateId'));

            return [
                'status' => 'new',
                'candidate_name' => $candidate->fullName,
                'title' => $candidate->title,
                'bilingual' => $candidate->bilingual,
                // Echoed back unchanged: the legacy candidate-update branch assigns each of these
                // from the request, so anything omitted here is wiped off the candidate record.
                'email' => $candidate->email,
                'phone' => $candidate->homePhone,
                'caddress' => $candidate->address,
                'caddress2' => $candidate->address2,
                'ccity' => $candidate->city,
                'cstate' => $candidate->state,
                'cpostalCode' => $candidate->postalCode,
            ];
        }

        $provider = $this->providersRepository->getById((int) $this->input('providerId'));

        return [
            'status' => 'current',
            'candidate_name' => $provider->fullName,
            'title' => $provider->DisciplineName,
            // The legacy form has no bilingual flag for an existing clinician (its checkbox stays
            // unchecked and posts 0).
            'bilingual' => 0,
        ];
    }
}
