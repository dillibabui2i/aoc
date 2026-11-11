<?php

namespace App\VBC\Services\Hospitalization;

use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Reads a form field's current value back out of whichever of the four places it is stored in, shaped
 * the way the JSON contract carries it. The read counterpart of HospitalizationWriteRepository's
 * per-field dispatch, so the resource and the Activity Log see exactly what the form sent.
 */
class HospitalizationTrackerFieldValues
{
    /**
     * @param  array<int, HospitalizationTrackerFieldEnum>  $fields
     * @return array<string, mixed>
     */
    public function readAll(HospitalizationTrackerModel $tracker, array $fields): array
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field->value] = $this->read($tracker, $field);
        }

        return $values;
    }

    public function read(HospitalizationTrackerModel $tracker, HospitalizationTrackerFieldEnum $field): mixed
    {
        $value = $this->raw($tracker, $field);

        // No stored value is not the same as no answer: a custom property that was never written reads
        // as its default, the same rule the write path uses to decide whether a write changes anything.
        return $this->present($field, $value ?? $field->defaultValue());
    }

    private function raw(HospitalizationTrackerModel $tracker, HospitalizationTrackerFieldEnum $field): mixed
    {
        $trackerColumn = $field->trackerColumn();
        if (!empty($trackerColumn)) {
            return $tracker->{$trackerColumn};
        }

        $encounterColumn = $field->encounterColumn();
        if (!empty($encounterColumn)) {
            return $tracker->encounter->{$encounterColumn};
        }

        $patientPropertyKey = $field->patientPropertyKey();
        if (!empty($patientPropertyKey)) {
            foreach ($tracker->encounter->patient?->customProperties ?? [] as $property) {
                if ($property->definition->key === $patientPropertyKey) {
                    return $property->value;
                }
            }

            return null;
        }

        $trackerPropertyKey = $field->trackerPropertyKey();
        foreach ($tracker->properties as $property) {
            if ($property->definition->key === $trackerPropertyKey) {
                return $property->value;
            }
        }

        return null;
    }

    /**
     * Enum columns come back as enum instances and dates as Carbon; the contract carries the backing
     * value and the format the form's datepicker speaks.
     */
    private function present(HospitalizationTrackerFieldEnum $field, mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        $format = $field->dateFormat();
        if (empty($format) || empty($value)) {
            return $value;
        }

        if ($value instanceof CarbonInterface) {
            return $value->format($format);
        }

        // EAV values are stored as plain date strings, so they need parsing back.
        try {
            return Carbon::parse($value)->format($format);
        } catch (Throwable) {
            return null;
        }
    }
}
