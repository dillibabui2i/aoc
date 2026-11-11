<?php

namespace App\Services\TrachVentClass;

use App\Enums\TrachVentClassCancellationScope;
use App\Events\TrachVentClass\TrachVentClassOccurrenceCancelled;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceReadRepository;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceWriteRepository;
use App\TrachVentClassOccurrence;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class TrachVentClassCancellationService
{
    public function __construct(
        private TrachVentClassOccurrenceReadRepository $trachVentClassOccurrenceReadRepository,
        private TrachVentClassOccurrenceWriteRepository $trachVentClassOccurrenceWriteRepository,
    ) {
    }

    /**
     * Cancels occurrences for the given scope and notifies the users who enrolled clinicians.
     *
     * Occurrences are hard deleted and enrollments cascade with them, so the affected
     * occurrences and users are collected before the delete runs. The delete and the
     * resulting notifications share one transaction so they commit or roll back together.
     * One event is fired per cancelled occurrence.
     *The listener notifies each user who enrolled clinicians into that occurrence.
     */
    public function cancel(TrachVentClassOccurrence $occurrence, TrachVentClassCancellationScope $scope): void
    {
        $occurrences = $this->resolveOccurrences($occurrence, $scope);

        if ($occurrences->isEmpty()) {
            return;
        }

        $occurrenceIds = $occurrences->pluck('id')->map(fn ($id) => (int) $id)->all();
        $enrollerIdsByOccurrence = $this->trachVentClassOccurrenceReadRepository
            ->findEnrollerIdsByOccurrence($occurrenceIds);

        DB::transaction(function () use ($occurrences, $occurrenceIds, $enrollerIdsByOccurrence) {
            $this->trachVentClassOccurrenceWriteRepository->deleteByIds($occurrenceIds);

            foreach ($occurrences as $cancelled) {
                $enrollerIds = $enrollerIdsByOccurrence->get((int) $cancelled->id, collect());

                if ($enrollerIds->isEmpty()) {
                    continue;
                }

                event(new TrachVentClassOccurrenceCancelled($cancelled, $enrollerIds->all()));
            }
        });
    }

    /**
     * @return Collection<int, TrachVentClassOccurrence>
     */
    private function resolveOccurrences(
        TrachVentClassOccurrence $occurrence,
        TrachVentClassCancellationScope $scope,
    ): Collection {
        return match ($scope) {
            TrachVentClassCancellationScope::OCCURRENCE => collect([$occurrence]),
            TrachVentClassCancellationScope::FUTURE => $this->trachVentClassOccurrenceReadRepository->findSeriesFrom($occurrence),
            TrachVentClassCancellationScope::SERIES => $this->trachVentClassOccurrenceReadRepository->findSeries($occurrence),
        };
    }
}
