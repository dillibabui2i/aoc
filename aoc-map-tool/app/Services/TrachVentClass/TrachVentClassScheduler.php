<?php

namespace App\Services\TrachVentClass;

use App\DTO\TrachVentClass\ScheduleTrachVentClassDTO;
use App\Enums\TrachVentClassRepeatEvery;
use App\TrachVentClass;
use App\TrachVentClassOccurrence;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

final readonly class TrachVentClassScheduler
{
    public function generate(ScheduleTrachVentClassDTO $dto): TrachVentClass
    {
        return DB::transaction(function () use ($dto) {
            $class = TrachVentClass::create($this->classAttributes($dto));

            $dates = $this->occurrenceDates($dto->classDate, $dto->repeatEvery, $dto->repeatOn, $dto->repeatUntil);

            $this->createOccurrences($class, $dates);

            return $class;
        });
    }

    private function classAttributes(ScheduleTrachVentClassDTO $dto): array
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
            'repeat_every' => $dto->repeatEvery?->value,
            'repeat_on' => $dto->repeatOn !== [] ? implode(',', $dto->repeatOn) : null,
            'repeat_until' => $dto->repeatUntil,
        ];
    }

    /**
     * @param array<int> $repeatOn ISO weekday numbers (1=Monday..7=Sunday), only used when $repeatEvery is DAY
     * @return array<string>
     */
    public function occurrenceDates(
        string $classDate,
        ?TrachVentClassRepeatEvery $repeatEvery,
        array $repeatOn,
        ?string $repeatUntil,
    ): array {
        if ($repeatEvery === null) {
            return [$classDate];
        }

        $start = Carbon::parse($classDate);
        $until = Carbon::parse($repeatUntil);

        if ($start->greaterThan($until)) {
            return [];
        }

        return match ($repeatEvery) {
            TrachVentClassRepeatEvery::DAY => $this->dailyDates($start, $until, $repeatOn),
            TrachVentClassRepeatEvery::WEEK => $this->steppedDates($start, $until, fn (Carbon $cursor) => $cursor->addWeek()),
            TrachVentClassRepeatEvery::MONTH => $this->steppedDates($start, $until, fn (Carbon $cursor) => $cursor->addMonthNoOverflow()),
        };
    }

    /**
     * @param array<string> $occurrenceDates
     */
    private function createOccurrences(TrachVentClass $class, array $occurrenceDates): void
    {
        $now = now();

        $rows = array_map(
            fn (string $classDate) => [
                'class_id' => $class->id,
                'class_date' => $classDate,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $occurrenceDates,
        );

        TrachVentClassOccurrence::insert($rows);
    }

    /**
     * @param array<int> $repeatOn ISO weekday numbers (1=Monday..7=Sunday)
     * @return array<string>
     */
    private function dailyDates(Carbon $start, Carbon $until, array $repeatOn): array
    {
        $dates = [];

        foreach (CarbonPeriod::create($start, $until) as $date) {
            if (in_array($date->isoWeekday(), $repeatOn, true)) {
                $dates[] = $date->toDateString();
            }
        }

        return $dates;
    }

    /**
     * @return array<string>
     */
    private function steppedDates(Carbon $start, Carbon $until, callable $step): array
    {
        $dates = [];
        $cursor = $start->copy();

        while ($cursor->lessThanOrEqualTo($until)) {
            $dates[] = $cursor->toDateString();
            $step($cursor);
        }

        return $dates;
    }
}
