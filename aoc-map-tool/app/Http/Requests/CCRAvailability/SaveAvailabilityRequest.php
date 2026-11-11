<?php

namespace App\Http\Requests\CCRAvailability;

use App\Enums\WeekDayEnum;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveAvailabilityRequest extends AvailabilityRequest
{
    /** Weekly capacity segments are configured per whole hour, within the PRC scheduling window. */
    private const CAPACITY_MIN_TIME = '08:00';
    private const CAPACITY_MAX_TIME = '17:00';
    private const CAPACITY_RANGE_MESSAGE = 'Weekly capacity: Capacity per hour must be a whole number from 1 to 99.';

    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'facility_id' => ['required', 'integer'],
            'blocks' => ['array'],
            'blocks.*.block_date' => ['required', 'date', 'after_or_equal:today'],
            'blocks.*.start_time' => ['required'],
            'blocks.*.end_time' => ['required', 'after:blocks.*.start_time'],
            'blocks.*.note' => ['nullable', 'string'],
            'segments' => ['array'],
            'segments.*.day_of_week' => ['required', Rule::enum(WeekDayEnum::class)],
            'segments.*.start_time' => ['required', 'date_format:g:ia'],
            'segments.*.end_time' => ['required', 'date_format:g:ia', 'after:segments.*.start_time'],
            'segments.*.capacity_per_hour' => ['required', 'integer', 'min:1', 'max:99'],
        ];
    }

    public function messages(): array
    {
        return [
            'blocks.*.end_time.after' => 'End time must be later than Start time.',
            'blocks.*.block_date.after_or_equal' => 'Date cannot be in the past.',
            'segments.*.day_of_week.*' => 'Weekly capacity: invalid day of week.',
            'segments.*.start_time.*' => 'Weekly capacity: Start time is required.',
            'segments.*.end_time.required' => 'Weekly capacity: End time is required.',
            'segments.*.end_time.date_format' => 'Weekly capacity: End time is required.',
            'segments.*.end_time.after' => 'Weekly capacity: End time must be later than Start time.',
            'segments.*.capacity_per_hour.*' => self::CAPACITY_RANGE_MESSAGE,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $segmentsByDay = [];
            foreach ($this->input('segments', []) as $segment) {
                $day = WeekDayEnum::from((int) $segment['day_of_week']);
                $start = date('H:i', strtotime($segment['start_time']));
                $end = date('H:i', strtotime($segment['end_time']));

                if (!str_ends_with($start, ':00') || !str_ends_with($end, ':00')) {
                    $validator->errors()->add('segments', "{$day->label()}: Start and End time must be on the hour.");
                }

                if ($start < self::CAPACITY_MIN_TIME || $end > self::CAPACITY_MAX_TIME) {
                    $validator->errors()->add(
                        'segments',
                        "{$day->label()}: time segments must be between 8:00am and 5:00pm."
                    );
                }

                $segmentsByDay[$day->value][] = ['start' => $start, 'end' => $end];
            }

            foreach ($segmentsByDay as $dayValue => $segments) {
                if ($this->hasOverlap($segments)) {
                    $dayLabel = WeekDayEnum::from($dayValue)->label();
                    $validator->errors()->add('segments', "{$dayLabel}: time segments must not overlap.");
                }
            }
        });
    }

    /**
     * @param array<int, array{start: string, end: string}> $segments End time is exclusive.
     */
    private function hasOverlap(array $segments): bool
    {
        usort($segments, fn (array $a, array $b) => strcmp($a['start'], $b['start']));

        for ($i = 1, $count = count($segments); $i < $count; $i++) {
            if ($segments[$i]['start'] < $segments[$i - 1]['end']) {
                return true;
            }
        }

        return false;
    }
}
