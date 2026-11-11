<?php

namespace App\Services\TrachVentClass;

use App\DTO\TrachVentClass\ClinicianEnrollmentDTO;
use App\DTO\TrachVentClass\EnrollmentResultDTO;
use App\MssqlPatients;
use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class ClinicianEnrollmentService
{
    private const ELIGIBLE_CLIENT_STATUSES = ['Active', 'Transferred', 'On-Hold', 'Intake'];

    public function __construct(
        private MssqlPatients $mssqlPatients,
    ) {
    }

    public function searchClients(string $term): Collection
    {
        if ($term === '') {
            return collect();
        }

        return $this->mssqlPatients->newQuery()
            ->leftJoin('mssql_patientstatus', 'mssql_patientstatus.PatientStatusID', '=', 'mssql_patients.Patient_CurrentStatusID')
            ->where('mssql_patients.PatientName', 'LIKE', "%{$term}%")
            ->whereIn('mssql_patientstatus.PatientStatus', self::ELIGIBLE_CLIENT_STATUSES)
            ->select(
                'mssql_patients.id',
                'mssql_patients.PatientName as name',
                DB::raw('mssql_patients.PatientName as label'),
                DB::raw('mssql_patients.PatientName as value'),
                'mssql_patientstatus.PatientStatus as status'
            )
            ->get();
    }

    public function handleEnroll(ClinicianEnrollmentDTO $dto, int $occurrenceId): EnrollmentResultDTO
    {
        $enrolledByUserId = Auth::id();

        return DB::transaction(function () use ($occurrenceId, $dto, $enrolledByUserId) {
            $occurrence = TrachVentClassOccurrence::where('id', $occurrenceId)->lockForUpdate()->first();

            if (!$occurrence) {
                return EnrollmentResultDTO::classNotFound();
            }

            $enrolledCount = $occurrence->enrolledCount();

            if ($enrolledCount >= (int) $occurrence->capacity) {
                return EnrollmentResultDTO::classFull();
            }

            $duplicateQuery = TrachVentClassRequest::where('trach_vent_class_occurrence_id', $occurrence->id)
                ->active();

            if ($dto->clinicianType === ClinicianEnrollmentDTO::CLINICIAN_TYPE_NEW) {
                $duplicateQuery->where('candidate_id', $dto->candidateId);
            } else {
                $duplicateQuery->where('provider_id', $dto->providerId);
            }

            if ($duplicateQuery->exists()) {
                return EnrollmentResultDTO::alreadyEnrolled();
            }

            TrachVentClassRequest::create([
                'trach_vent_class_occurrence_id' => $occurrence->id,
                'provider_id' => $dto->providerId,
                'candidate_id' => $dto->candidateId,
                'client_id' => $dto->clientId,
                'discipline_snapshot' => $dto->discipline,
                'phone_number_snapshot' => $dto->phoneNumber,
                'start_date' => $dto->startDate,
                'created_by' => $enrolledByUserId,
                'updated_by' => $enrolledByUserId,
            ]);

            $enrolledCount++;

            return EnrollmentResultDTO::enrolled($enrolledCount, (int) $occurrence->capacity);
        });
    }
}
