<?php

namespace App\PrivateAPI\Support;

use InvalidArgumentException;

final class ScheduleRemoval
{
    public const REASON_CATEGORY = 'Clinician';
    public const REASON = 'Staffed';
    private const MODE_SINGLE = 'remove';
    private const MODE_BULK = 'removeall';

    public readonly array $shiftIds;

    public function __construct(public readonly int $nthId, array $shiftIds)
    {
        if ($nthId < 1) {
            throw new InvalidArgumentException('A schedule removal needs an NTH.');
        }

        if ($shiftIds === []) {
            throw new InvalidArgumentException('A schedule removal needs at least one shift.');
        }

        $this->shiftIds = array_values(array_map('intval', $shiftIds));
    }

    public function toFormFields(): array
    {
        return [
            ['name' => 'aoc_nthaccounts_id', 'value' => (string)$this->nthId],
            ['name' => 'item_id', 'value' => implode(',', $this->shiftIds)],
            ['name' => 'mode', 'value' => $this->mode()],
            ['name' => 'reason_category', 'value' => self::REASON_CATEGORY],
            ['name' => 'reason', 'value' => self::REASON],
        ];
    }

    private function mode(): string
    {
        return count($this->shiftIds) > 1 ? self::MODE_BULK : self::MODE_SINGLE;
    }
}
