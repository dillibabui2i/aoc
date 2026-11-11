<?php

namespace App\VBC\Services\Immunization;

use App\VBC\DTO\Immunization\ImmunizationScheduleDTO;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use App\VBC\Repositories\Immunization\ImmunizationScheduleReadRepository;
use App\VBC\Repositories\Immunization\ImmunizationScheduleWriteRepository;
use Illuminate\Support\Facades\DB;

class ImmunizationScheduleService
{
    public function __construct(
        private readonly ImmunizationScheduleWriteRepository $writeRepository,
        private readonly ImmunizationScheduleReadRepository $readRepository,
    ) {
    }

    public function create(ImmunizationScheduleDTO $dto): ImmunizationRecommendationModel
    {
        $schedule = DB::transaction(function () use ($dto) {
            $schedule = $this->writeRepository->create($dto);
            $this->writeRepository->replaceDoseAges($schedule, $dto->doseAgeTargets);
            $this->writeRepository->replaceRecommendedFor($schedule, $dto->recommendedFor);

            return $schedule;
        });

        return $this->readRepository->findForEdit($schedule->id);
    }

    public function update(ImmunizationRecommendationModel $schedule, ImmunizationScheduleDTO $dto): ImmunizationRecommendationModel
    {
        DB::transaction(function () use ($schedule, $dto) {
            $this->writeRepository->update($schedule, $dto);
            $this->writeRepository->replaceDoseAges($schedule, $dto->doseAgeTargets);
            $this->writeRepository->replaceRecommendedFor($schedule, $dto->recommendedFor);
        });

        return $this->readRepository->findForEdit($schedule->id);
    }
}
