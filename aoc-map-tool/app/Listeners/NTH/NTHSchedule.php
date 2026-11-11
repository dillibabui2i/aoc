<?php

namespace App\Listeners\NTH;

use App\Events\NTH\NTHScheduleChanged;
use App\Jobs\NTH\ScheduleChangeEventPublishedJob;
use App\Repositories\NeedToHireAccounts\NeedToHireAccountsReadRepository;
use App\Services\Patient\PatientService;

class NTHSchedule
{
    private NeedToHireAccountsReadRepository $needToHireAccountsReadRepository;
    private PatientService $patientService;

    public function __construct(
        NeedToHireAccountsReadRepository $needToHireAccountsReadRepository,
        PatientService $patientService
    ) {
        $this->needToHireAccountsReadRepository = $needToHireAccountsReadRepository;
        $this->patientService = $patientService;
    }

    public function handle(NTHScheduleChanged $event): void
    {
        $data = $this->needToHireAccountsReadRepository->getAccountsFilteredByNthAccountID(
            $event->getAocNthAccountsID()
        );

        ScheduleChangeEventPublishedJob::dispatch(
            $event,
            $this->patientService->getPatientCareTeam($data->profileID),
            $data->profileID
        )->delay(now()->addMinutes(2));
    }
}
