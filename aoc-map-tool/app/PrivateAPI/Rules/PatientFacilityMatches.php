<?php

namespace App\PrivateAPI\Rules;

use App\MssqlFacilities;
use App\MssqlPatients;
use App\Repositories\MssqlFacilities\MssqlFacilitiesRepository;
use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use Illuminate\Contracts\Validation\Rule;

/**
 * Validates facilityId against the patient's own aoc_facility_id (when the patient has one), and
 * resolves both rows along the way so the caller doesn't have to fetch them a second time to
 * build the legacy payload afterward.
 */
class PatientFacilityMatches implements Rule
{
    private ?MssqlPatients $patient = null;

    private ?MssqlFacilities $facility = null;

    public function __construct(
        private readonly MssqlPatientsReadRepository $patients,
        private readonly MssqlFacilitiesRepository $facilities,
        private readonly mixed $patientId,
    ) {
    }

    public function passes($attribute, $value): bool
    {
        $this->patient = $this->patients->findById((int) $this->patientId);
        $patientFacilityId = $this->patient?->aoc_facility_id;

        $this->facility = $this->facilities->getById((int) ($patientFacilityId ?: $value));

        return $patientFacilityId === null || (int) $patientFacilityId === (int) $value;
    }

    public function message(): string
    {
        return "facilityId does not match the patient's facility.";
    }

    public function getPatient(): ?MssqlPatients
    {
        return $this->patient;
    }

    public function getFacility(): ?MssqlFacilities
    {
        return $this->facility;
    }
}
