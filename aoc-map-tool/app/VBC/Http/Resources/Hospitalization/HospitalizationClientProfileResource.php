<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\Enums\MssqlPatientStatus;
use App\MssqlPatients;
use App\Services\Intake\IntakeService;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyModel;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * The client-derived half of the "Hospitalization Record" form. Used on its own to pre-fill the form the
 * moment a client is picked (before any tracker exists), and merged into HospitalizationTrackerResource
 * afterwards — one mapping, so the pre-fill and the saved record can never show different values.
 *
 * Everything here is read-only apart from the three toggles, which live on the client profile rather
 * than the tracker (see HospitalizationTrackerFieldEnum).
 */
class HospitalizationClientProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var MssqlPatients $patient */
        $patient = $this->resource;

        return [
            'patient_id' => $patient->id,
            'patient_name' => $patient->PatientName,
            'client_id' => $patient->PatientAccount,
            'date_of_birth' => $this->dateOfBirth($patient)?->format(config('vbc.date_format')),
            'age' => $this->dateOfBirth($patient)?->age,
            'medicaid_id' => $patient->MedicaidNumber,
            'client_status_kt' => MssqlPatientStatus::tryFrom((int) $patient->Patient_CurrentStatusID)?->label(),
            'terminal_payer' => $patient->CF_TerminalPayer,
            'branch' => $patient->facility?->FacilityName,
            'auth_hours_per_week' => $this->authHoursPerWeek($patient),
            // No confirmed source for DME Provider yet; the field is displayed but has nothing to read.
            'dme_provider' => null,
            'high_utilizer' => $this->propertyValue($patient, HospitalizationTrackerFieldEnum::HIGH_UTILIZER),
            'backup_g_tube' => $this->propertyValue($patient, HospitalizationTrackerFieldEnum::BACKUP_G_TUBE),
            'backup_trach' => $this->propertyValue($patient, HospitalizationTrackerFieldEnum::BACKUP_TRACH),
            'backup_g_tube_last_updated' => $this->propertyUpdatedAt($patient, HospitalizationTrackerFieldEnum::BACKUP_G_TUBE),
            'backup_trach_last_updated' => $this->propertyUpdatedAt($patient, HospitalizationTrackerFieldEnum::BACKUP_TRACH),
        ];
    }

    /** mssql_patients casts nothing, so Patient_DOB arrives as a raw string (and can be '0000-00-00'). */
    private function dateOfBirth(MssqlPatients $patient): ?Carbon
    {
        $dob = $patient->Patient_DOB;
        if (empty($dob) || str_starts_with((string) $dob, '0000')) {
            return null;
        }

        try {
            return Carbon::parse($dob);
        } catch (Throwable) {
            return null;
        }
    }

    /** Which "authorized hours" column applies depends on the client's line of business. */
    private function authHoursPerWeek(MssqlPatients $patient): int|float|null
    {
        return $patient->{app(IntakeService::class)->resolvePatientHoursField((int) $patient->LOBID)};
    }

    private function propertyValue(MssqlPatients $patient, HospitalizationTrackerFieldEnum $field): ?bool
    {
        $property = $this->property($patient, $field);

        // A property that was never written reads as its default, not as "unanswered" — same rule the
        // section's toggles follow, so an off toggle looks the same wherever it is shown.
        return empty($property) ? $field->defaultValue() : (bool) $property->value;
    }

    /**
     * The property row's own updated_at is the "last updated" the form shows — nothing else stores it.
     *
     * Only reported while the flag is on: read next to a toggle that is off, a date reads as nonsense
     * ("there is no backup G-tube, but here is when it was updated"). Turning a flag off leaves the row
     * behind with its timestamp, so this has to be filtered here rather than relying on the row's
     * absence.
     */
    private function propertyUpdatedAt(MssqlPatients $patient, HospitalizationTrackerFieldEnum $field): ?string
    {
        $property = $this->property($patient, $field);

        if (empty($property) || empty($property->value)) {
            return null;
        }

        return $property->updated_at?->format(config('vbc.date_time_format'));
    }

    private function property(MssqlPatients $patient, HospitalizationTrackerFieldEnum $field): ?PatientCustomPropertyModel
    {
        foreach ($patient->customProperties as $property) {
            if ($property->definition->key === $field->patientPropertyKey()) {
                return $property;
            }
        }

        return null;
    }
}
