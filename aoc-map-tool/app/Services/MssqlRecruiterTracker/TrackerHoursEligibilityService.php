<?php

namespace App\Services\MssqlRecruiterTracker;

use App\DTO\MssqlRecruiterTracker\TrackerHoursEligibilityResultDTO;
use App\Repositories\MssqlRecruiterTracker\KantimeBillableHoursReadRepository;
use App\Repositories\MssqlRecruiterTracker\MssqlRecruiterTrackerReadRepository;

class TrackerHoursEligibilityService
{
    public function __construct(
        private KantimeBillableHoursReadRepository $kantimeBillableHoursReadRepository,
        private MssqlRecruiterTrackerReadRepository $trackerReadRepository
    ) {}

    public function checkEligibility(int $aocProviderId, int $aocPatientId, float $totalHours): TrackerHoursEligibilityResultDTO
    {
        $highestWeeklyHours = $this->kantimeBillableHoursReadRepository
            ->getHighestWeeklyBillableHours($aocProviderId, $aocPatientId);

        if ($highestWeeklyHours < $totalHours) {
            return TrackerHoursEligibilityResultDTO::allowed();
        }

        $existingTracker = $this->trackerReadRepository
            ->findLatestForProviderAndPatient($aocProviderId, $aocPatientId);

        return TrackerHoursEligibilityResultDTO::blocked(
            $highestWeeklyHours,
            $existingTracker?->id
        );
    }
}
