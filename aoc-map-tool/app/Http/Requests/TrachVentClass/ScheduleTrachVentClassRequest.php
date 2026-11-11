<?php

namespace App\Http\Requests\TrachVentClass;

use App\DTO\TrachVentClass\ScheduleTrachVentClassDTO;
use App\Enums\TrachVentClassRepeatEvery;
use App\Http\Requests\CoreRequest;
use App\Services\TrachVentClass\TrachVentClassScheduler;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ScheduleTrachVentClassRequest extends CoreRequest
{
    public function rules(): array
    {
        $repeatEvery = $this->input('repeat_every');
        $repeatEveryValues = array_column(TrachVentClassRepeatEvery::cases(), 'value');

        return [
            'type' => ['required'],
            'location_id' => ['required', 'integer'],
            'instructor_id' => ['nullable', 'integer'],
            'class_date' => ['required', 'date'],
            'class_time' => ['required'],
            'end_time' => ['required', 'after:class_time'],
            'capacity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'class_type' => ['nullable', 'string'],
            'repeat_every' => ['nullable', Rule::in(['none', ...$repeatEveryValues])],
            'repeat_on' => $repeatEvery === TrachVentClassRepeatEvery::DAY->value
                ? ['required', 'array', 'min:1']
                : ['nullable', 'array'],
            'repeat_on.*' => ['integer', 'between:1,7'],
            'repeat_until' => in_array($repeatEvery, $repeatEveryValues, true)
                ? ['required', 'date', 'after_or_equal:class_date']
                : ['nullable', 'date'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            [$classDate, $repeatEvery, $repeatOn, $repeatUntil] = $this->resolvedRecurrence($validator->getData());

            $dates = app(TrachVentClassScheduler::class)->occurrenceDates($classDate, $repeatEvery, $repeatOn, $repeatUntil);

            if ($dates === []) {
                $validator->errors()->add('repeat_on', 'The selected recurrence does not produce any class occurrences.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'end_time.required' => 'Select an end time.',
            'end_time.after' => 'End time must be later than the start time.',
        ];
    }

    public function toDTO(): ScheduleTrachVentClassDTO
    {
        $data = $this->validated();
        [$classDate, $repeatEvery, $repeatOn, $repeatUntil] = $this->resolvedRecurrence($data);

        return new ScheduleTrachVentClassDTO(
            type: $data['type'],
            locationId: (int) $data['location_id'],
            instructorId: isset($data['instructor_id']) ? (int) $data['instructor_id'] : null,
            classDate: $classDate,
            classTime: date('H:i', strtotime($data['class_time'])),
            endTime: date('H:i', strtotime($data['end_time'])),
            capacity: (int) $data['capacity'],
            notes: $data['notes'] ?? null,
            classType: $data['class_type'] ?? null,
            repeatEvery: $repeatEvery,
            repeatOn: $repeatOn,
            repeatUntil: $repeatUntil,
        );
    }

    /**
     * @return array{0: string, 1: ?TrachVentClassRepeatEvery, 2: array<int>, 3: ?string}
     */
    private function resolvedRecurrence(array $data): array
    {
        $classDate = date('Y-m-d', strtotime($data['class_date']));
        $repeatEvery = TrachVentClassRepeatEvery::tryFrom($data['repeat_every'] ?? '');

        if ($repeatEvery === null) {
            return [$classDate, null, [], null];
        }

        $repeatOn = array_map('intval', $data['repeat_on'] ?? []);
        $repeatUntil = date('Y-m-d', strtotime($data['repeat_until']));

        return [$classDate, $repeatEvery, $repeatOn, $repeatUntil];
    }
}
