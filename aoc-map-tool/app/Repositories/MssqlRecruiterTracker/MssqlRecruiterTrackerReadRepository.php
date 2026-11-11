<?php

namespace App\Repositories\MssqlRecruiterTracker;

use App\Enums\MssqlRecreturTrackerStatus;
use App\MssqlRecruitertracker;
use Illuminate\Support\LazyCollection;

class MssqlRecruiterTrackerReadRepository
{
    public function __construct(private MssqlRecruitertracker $tracker)
    {
    }

    public function getActiveNRNCTrackers(int $trackerId, int $providerId): LazyCollection
    {
        return $this->tracker->query()
            ->where('is_active', 1)
            ->where('Candidate_Current_Status', MssqlRecreturTrackerStatus::NEE_TO_REVIEW_NEW_CLIENTS->value)
            ->where('aoc_provider_id', $providerId)
            ->whereNot('id', $trackerId)
            ->cursor();
    }

    public function getById(int $trackerId): ?MssqlRecruitertracker
    {
        return $this->tracker->query()->find($trackerId);
    }

    public function hasActiveTrackerForPatient(int $patientId, ?int $candidateId, ?int $providerId): bool
    {
        if (empty($candidateId) && empty($providerId)) {
            return false;
        }

        return $this->tracker->query()
            ->where('aoc_patient_id', $patientId)
            ->where('is_active', 1)
            ->where('Candidate_Current_Status', '!=', 'Inactive')
            ->where(function ($query) use ($candidateId, $providerId) {
                if (!empty($candidateId)) {
                    $query->orWhere('aoc_candidate_id', $candidateId);
                }

                if (!empty($providerId)) {
                    $query->orWhere('aoc_provider_id', $providerId);
                }
            })
            ->exists();
    }

    public function findLatestForProviderAndPatient(int $aocProviderId, int $aocPatientId): ?MssqlRecruitertracker
    {
        return $this->tracker->query()
            ->where('aoc_provider_id', $aocProviderId)
            ->where('aoc_patient_id', $aocPatientId)
            ->orderByDesc('id')
            ->first();
    }
}
