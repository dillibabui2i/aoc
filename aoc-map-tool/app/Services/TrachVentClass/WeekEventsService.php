<?php

namespace App\Services\TrachVentClass;

use App\DTO\TrachVentClass\WeekEventsFilterDTO;
use App\DTO\TrachVentClass\WeekEventsViewDataDTO;
use App\Helpers\Calendar;
use App\Helpers\ConstantHelper;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceReadRepository;
use App\Repositories\User\UserReadRepository;
use App\Transformer\TrachVentClass\TrachVentClassOccurrenceTransformer;

final readonly class WeekEventsService
{
    public function __construct(
        private TrachVentClassOccurrenceReadRepository $trachVentClassOccurrenceReadRepository,
        private UserReadRepository $userReadRepository,
        private TrachVentClassOccurrenceTransformer $trachVentClassOccurrenceTransformer,
        private ConstantHelper $constantHelper,
    ) {
    }

    public function build(WeekEventsFilterDTO $dto): WeekEventsViewDataDTO
    {
        $branches = $this->constantHelper->getBranches()->toArray();
        $week = Calendar::getWeekStartEndByDates($dto->classDate);
        $states = $this->trachVentClassOccurrenceReadRepository->findBranchLocationIdsByState($dto->state);

        $branchData = [];
        if ($dto->branchId !== null) {
            $branchLocation = $this->trachVentClassOccurrenceReadRepository->findBranchLocation($dto->branchId);
            $branchData = $branchLocation !== null ? $branchLocation->toArray() : [];
        }

        $schedulesModels = $this->trachVentClassOccurrenceReadRepository->forWeek(
            $week['start'],
            $week['end'],
            $states,
            $dto->branchId,
        );

        $instructorIds = $schedulesModels->pluck('instructor_id')
            ->filter()
            ->unique()
            ->toArray();
        $instructors = $this->userReadRepository->getInstructorsByIds($instructorIds)
            ->pluck('name', 'id')
            ->toArray();

        $scheduleData = $this->trachVentClassOccurrenceTransformer->transformWeekEvents($schedulesModels, $branches, $instructors);

        return new WeekEventsViewDataDTO(
            scheduleData: $scheduleData,
            branches: $branches,
            branchData: $branchData,
            week: $week,
            state: $dto->state,
        );
    }
}
