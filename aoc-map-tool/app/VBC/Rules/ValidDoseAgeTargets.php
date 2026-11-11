<?php

namespace App\VBC\Rules;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use Illuminate\Contracts\Validation\Rule;

/**
 * Business rules of the "Dose Age Targets" section: value ranges per unit (months 0–23, years 2 and up), range consistency, and no
 * duplicate / overlapping / contained targets within one vaccine. Row-shape checks (types, enum values)
 * live in the request's per-row rules; a row that is malformed there is skipped here so each problem is
 * reported once.
 *
 * Ages are compared in months. A range is half-open: the start is inclusive, the end is not, so a range may
 * start where the previous one ends (0–3 and 3–6 months are valid). A target without `to_range` is the single
 * point [from, from]; it is taken by a range that includes it (0 ≤ p < 3) but not by one that ends on it.
 */
class ValidDoseAgeTargets implements Rule
{
    private const MAX_MONTHS = 23;
    private const MIN_YEARS = 2;

    /** @var array<int, string> */
    private array $messages = [];

    public function passes($attribute, $value): bool
    {
        $this->messages = [];

        if (!is_array($value)) {
            return true;
        }

        $intervals = [];

        foreach (array_values($value) as $index => $target) {
            $interval = $this->validateTarget($target, $index + 1);

            if ($interval !== null) {
                $intervals[$index + 1] = $interval;
            }
        }

        $this->checkPairs($intervals);

        return $this->messages === [];
    }

    /** @return array<int, string> */
    public function message(): array
    {
        return $this->messages;
    }

    /** @return array{0: int, 1: int}|null [from, to] in months, null when the row is invalid or malformed. */
    private function validateTarget(mixed $target, int $number): ?array
    {
        if (!is_array($target) || !is_int($target['from_age'] ?? null)) {
            return null;
        }

        $fromUnit = ImmunizationDoseAgeUnitEnum::tryFrom((string) ($target['from_age_unit'] ?? ''));

        if ($fromUnit === null) {
            return null;
        }

        $errors = $this->unitRangeError($target['from_age'], $fromUnit, 'Start value', allowZero: true);

        if (!filter_var($target['to_range'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return $this->finish($errors, $number, [$fromUnit->toMonths($target['from_age']), $fromUnit->toMonths($target['from_age'])]);
        }

        $toAge = $target['to_age'] ?? null;
        $toUnit = ImmunizationDoseAgeUnitEnum::tryFrom((string) ($target['to_age_unit'] ?? ''));

        if (!is_int($toAge) || $toUnit === null) {
            $errors[] = 'End value and end unit are required when the dose is a range.';

            return $this->finish($errors, $number, null);
        }

        if ($toUnit !== $fromUnit) {
            $errors[] = 'Start and end units must match.';
        }

        $errors = array_merge($errors, $this->unitRangeError($toAge, $toUnit, 'End value', allowZero: false));

        if ($toUnit === $fromUnit && $toAge > 0 && $toAge <= $target['from_age']) {
            $errors[] = 'End value must be greater than the start value.';
        }

        return $this->finish($errors, $number, [$fromUnit->toMonths($target['from_age']), $toUnit->toMonths($toAge)]);
    }

    /**
     * @param  array<int, string>  $errors
     * @param  array{0: int, 1: int}|null  $interval
     * @return array{0: int, 1: int}|null
     */
    private function finish(array $errors, int $number, ?array $interval): ?array
    {
        foreach ($errors as $error) {
            $this->messages[] = "Dose target #{$number}: {$error}";
        }

        return $errors === [] ? $interval : null;
    }

    /** @return array<int, string> */
    private function unitRangeError(int $age, ImmunizationDoseAgeUnitEnum $unit, string $label, bool $allowZero): array
    {
        if ($age === 0 && !$allowZero) {
            return ["{$label} must be greater than 0."];
        }

        return match ($unit) {
            ImmunizationDoseAgeUnitEnum::MONTH => $age > self::MAX_MONTHS
                ? ["{$label} in months must be " . self::MAX_MONTHS . ' or lower.']
                : [],
            ImmunizationDoseAgeUnitEnum::YEAR => $age < self::MIN_YEARS
                ? ["{$label} in years must be " . self::MIN_YEARS . ' or higher.']
                : [],
        };
    }

    /** @param array<int, array{0: int, 1: int}> $intervals keyed by 1-based row number */
    private function checkPairs(array $intervals): void
    {
        $numbers = array_keys($intervals);

        foreach ($numbers as $i => $a) {
            foreach (array_slice($numbers, $i + 1) as $b) {
                $message = $this->pairError($intervals[$a], $intervals[$b]);

                if ($message !== null) {
                    $this->messages[] = "Dose targets #{$a} and #{$b} {$message}";
                }
            }
        }
    }

    /**
     * @param  array{0: int, 1: int}  $x
     * @param  array{0: int, 1: int}  $y
     */
    private function pairError(array $x, array $y): ?string
    {
        if ($x === $y) {
            return 'are duplicates.';
        }

        $xIsPoint = $x[0] === $x[1];
        $yIsPoint = $y[0] === $y[1];

        if ($xIsPoint || $yIsPoint) {
            return $this->pointError($xIsPoint ? $x[0] : $y[0], $xIsPoint ? $y : $x);
        }

        $contained = ($x[0] >= $y[0] && $x[1] <= $y[1]) || ($y[0] >= $x[0] && $y[1] <= $x[1]);

        if ($contained) {
            return 'are not allowed: one range is contained in the other.';
        }

        return $x[0] < $y[1] && $y[0] < $x[1] ? 'overlap.' : null;
    }

    /**
     * @param  array{0: int, 1: int}  $other  a range, or another point (then only a duplicate clashes, caught earlier)
     */
    private function pointError(int $point, array $other): ?string
    {
        if ($other[0] === $other[1]) {
            return null;
        }

        return $point >= $other[0] && $point < $other[1]
            ? 'are not allowed: one range is contained in the other.'
            : null;
    }
}
