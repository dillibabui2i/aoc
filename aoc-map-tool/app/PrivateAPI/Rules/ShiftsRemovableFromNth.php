<?php

namespace App\PrivateAPI\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ShiftsRemovableFromNth implements Rule
{
    private string $message = 'The selected shifts are invalid.';

    public function __construct(private readonly mixed $nthId)
    {
    }

    public function passes($attribute, $value): bool
    {
        if (!is_array($value) || $value === [] || !is_numeric($this->nthId)) {
            return true;
        }

        foreach ($value as $shiftId) {
            if (!is_numeric($shiftId)) {
                return true;
            }
        }

        $nthId = (int)$this->nthId;
        $shiftIds = array_map('intval', $value);

        $shifts = DB::table('NeedToHire_Schedules')
            ->whereIn('id', $shiftIds)
            ->get(['id', 'aoc_nthaccounts_id', 'IsActive'])
            ->keyBy('id');

        foreach ($shiftIds as $shiftId) {
            $shift = $shifts->get($shiftId);

            if (!$shift) {
                $this->message = 'Shift ' . $shiftId . ' does not exist.';

                return false;
            }

            if ((int)$shift->aoc_nthaccounts_id !== $nthId) {
                $this->message = 'Shift ' . $shiftId . ' does not belong to NTH ' . $nthId . '.';

                return false;
            }

            if ((int)$shift->IsActive !== 1) {
                $this->message = 'Shift ' . $shiftId . ' is already closed.';

                return false;
            }
        }

        return true;
    }

    public function message(): string
    {
        return $this->message;
    }
}
