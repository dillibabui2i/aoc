<?php

namespace App\Services\TrachVentClass;

use App\DTO\TrachVentClass\UpdateTrachVentClassDTO;
use App\Exceptions\TrachVentClassScheduleException;
use App\TrachVentClass;
use App\TrachVentClassOccurrence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final readonly class TrachVentClassUpdater
{
    public function __construct(
        private TrachVentClassScheduler $trachVentClassScheduler,
    ) {
    }

    public function update(UpdateTrachVentClassDTO $dto): void
    {
        DB::transaction(function () use ($dto) {
            $class = TrachVentClass::withCount('occurrences')->findOrFail($dto->classId);

            $previousRepeatUntil = $class->repeat_until?->toDateString();
            $repeatUntil = $dto->repeatUntil ?? ($class->repeat_until?->toDateString());

            $shouldSyncOccurrences = $class->isRecurring() && $repeatUntil !== null && $repeatUntil !== $previousRepeatUntil;

            $class->update([
                ...$this->classAttributes($dto),
                'repeat_until' => $repeatUntil,
            ]);

            if ($shouldSyncOccurrences) {
                $this->syncOccurrenceDates($class->fresh(), $repeatUntil);
            }
        });
    }

    private function classAttributes(UpdateTrachVentClassDTO $dto): array
    {
        return [
            'type' => $dto->type,
            'location_id' => $dto->locationId,
            'instructor_id' => $dto->instructorId,
            'class_date' => $dto->classDate,
            'class_time' => $dto->classTime,
            'end_time' => $dto->endTime,
            'capacity' => $dto->capacity,
            'notes' => $dto->notes,
            'class_type' => $dto->classType,
        ];
    }

    private function syncOccurrenceDates(TrachVentClass $class, string $repeatUntil): void
    {
        $repeatEvery = $class->repeat_every;

        if ($repeatEvery === null) {
            return;
        }

        $occurrences = TrachVentClassOccurrence::where('class_id', $class->id)->get();
        $existingDates = $occurrences->pluck('class_date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->all();
        $startDate = $existingDates !== [] ? min($existingDates) : $class->class_date;

        $desiredDates = $this->trachVentClassScheduler->occurrenceDates(
            $startDate,
            $repeatEvery,
            array_map('intval', $class->repeatOnDays()),
            $repeatUntil,
        );

        if ($desiredDates === []) {
            throw new TrachVentClassScheduleException('The selected recurrence does not produce any class occurrences.');
        }

        $extraOccurrenceIds = $occurrences
            ->filter(fn (TrachVentClassOccurrence $occurrence) => !in_array(
                Carbon::parse($occurrence->class_date)->toDateString(),
                $desiredDates,
                true,
            ))
            ->pluck('id');

        if (TrachVentClassOccurrence::whereIn('id', $extraOccurrenceIds)->whereHas('enrollments')->exists()) {
            throw new TrachVentClassScheduleException('Cannot remove occurrences that already have enrollments.');
        }

        TrachVentClassOccurrence::whereIn('id', $extraOccurrenceIds)->delete();
        $this->createMissingOccurrences($class, array_values(array_diff($desiredDates, $existingDates)));
    }

    /**
     * @param array<string> $missingDates
     */
    private function createMissingOccurrences(TrachVentClass $class, array $missingDates): void
    {
        if ($missingDates === []) {
            return;
        }

        $now = now();

        TrachVentClassOccurrence::insert(array_map(
            fn (string $classDate) => [
                'class_id' => $class->id,
                'class_date' => $classDate,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $missingDates,
        ));
    }
}
