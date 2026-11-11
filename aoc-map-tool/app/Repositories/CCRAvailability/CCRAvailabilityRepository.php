<?php

namespace App\Repositories\CCRAvailability;

use App\AvailabilityBlock;
use App\AvailabilityCapacity;
use App\Helpers\Calendar;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CCRAvailabilityRepository
{
    public function __construct(
        private AvailabilityBlock $availabilityBlock,
        private AvailabilityCapacity $availabilityCapacity,
        private Calendar $calendarHelper
    ) {
    }

    public function getBlocks(int $facilityId): Collection
    {
        return $this->availabilityBlock->newQuery()
            ->where('aoc_facility_id', $facilityId)
            ->orderBy('block_date')
            ->orderBy('start_time')
            ->get();
    }

    public function getCapacitySegments(int $facilityId): Collection
    {
        return $this->availabilityCapacity->newQuery()
            ->where('aoc_facility_id', $facilityId)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get();
    }

    /**
     * @param array<int> $facilityIds
     */
    public function getBlocksForFacilityInRange(array $facilityIds, string $startDate, string $endDate): Collection
    {
        return $this->availabilityBlock->newQuery()
            ->join('mssql_facilities', 'mssql_facilities.id', '=', 'ccr_availability_block.aoc_facility_id')
            ->whereIn('ccr_availability_block.aoc_facility_id', $facilityIds)
            ->whereBetween('ccr_availability_block.block_date', [$startDate, $endDate])
            ->select('ccr_availability_block.*', 'mssql_facilities.FacilityName as facility_name')
            ->orderBy('ccr_availability_block.block_date')
            ->orderBy('ccr_availability_block.start_time')
            ->get();
    }

    public function getBlocksForStateInRange(string $state, string $startDate, string $endDate): Collection
    {
        return $this->availabilityBlock->newQuery()
            ->join('mssql_facilities', 'mssql_facilities.id', '=', 'ccr_availability_block.aoc_facility_id')
            ->where('mssql_facilities.state', $state)
            ->whereBetween('ccr_availability_block.block_date', [$startDate, $endDate])
            ->select('ccr_availability_block.*', 'mssql_facilities.FacilityName as facility_name')
            ->orderBy('ccr_availability_block.block_date')
            ->orderBy('ccr_availability_block.start_time')
            ->get();
    }

    public function findBlock(int $id): ?AvailabilityBlock
    {
        return $this->availabilityBlock->newQuery()->find($id);
    }

    /**
     * @param array{block_date: string, start_time: string, end_time: string, note: ?string} $data
     */
    public function updateBlock(int $id, array $data, int $actingUserId): void
    {
        $this->availabilityBlock->newQuery()->where('id', $id)->update([
            'block_date' => $data['block_date'],
            'start_time' => $this->normalizeTime($data['start_time']),
            'end_time' => $this->normalizeTime($data['end_time']),
            'note' => $data['note'] ?? null,
            'updated_by' => $actingUserId,
        ]);
    }

    public function deleteBlock(int $id): void
    {
        $this->availabilityBlock->newQuery()->where('id', $id)->delete();
    }

    /**
     * Buckets blocked-availability rows into the same {hour-label}[{day-name}][] shape
     * used by the calendar week grid, so they can render alongside scheduled events.
     */
    public function bucketBlocksByHourAndDay(Collection $blocks): array
    {
        $bucketed = [];

        foreach ($blocks as $block) {
            $day = date('l', strtotime($block->block_date));
            $bucket = $this->calendarHelper->clampToHourBucket($block->start_time);

            $bucketed[$bucket][$day][] = $block;
        }

        return $bucketed;
    }

    /**
     * Full-replace save: deletes all existing block and weekly capacity rows
     * for the facility and inserts the submitted sets, in a transaction.
     *
     * @param array<int, array{block_date: string, start_time: string, end_time: string, note: ?string}> $blocks
     * @param array<int, array{
     *     day_of_week: int, start_time: string, end_time: string, capacity_per_hour: int
     * }> $segments
     */
    public function saveForFacility(int $facilityId, array $blocks, array $segments, int $actingUserId): void
    {
        DB::transaction(function () use ($facilityId, $blocks, $segments, $actingUserId) {
            $this->availabilityBlock->newQuery()->where('aoc_facility_id', $facilityId)->delete();

            $now = now();

            $blockRows = [];
            foreach ($blocks as $block) {
                $blockRows[] = [
                    'aoc_facility_id' => $facilityId,
                    'block_date' => $block['block_date'],
                    'start_time' => $this->normalizeTime($block['start_time']),
                    'end_time' => $this->normalizeTime($block['end_time']),
                    'note' => $block['note'] ?? null,
                    'created_by' => $actingUserId,
                    'updated_by' => $actingUserId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($blockRows)) {
                $this->availabilityBlock->newQuery()->insert($blockRows);
            }

            $this->saveCapacitySegments($facilityId, $segments, $actingUserId, $now);
        });
    }

    /**
     * @param array<int, array{
     *     day_of_week: int, start_time: string, end_time: string, capacity_per_hour: int
     * }> $segments
     */
    private function saveCapacitySegments(int $facilityId, array $segments, int $actingUserId, Carbon $now): void
    {
        $this->availabilityCapacity->newQuery()->where('aoc_facility_id', $facilityId)->delete();

        $segmentRows = [];
        foreach ($segments as $segment) {
            $segmentRows[] = [
                'aoc_facility_id' => $facilityId,
                'day_of_week' => (int) $segment['day_of_week'],
                'start_time' => $this->normalizeTime($segment['start_time']),
                'end_time' => $this->normalizeTime($segment['end_time']),
                'capacity_per_hour' => (int) $segment['capacity_per_hour'],
                'created_by' => $actingUserId,
                'updated_by' => $actingUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($segmentRows)) {
            $this->availabilityCapacity->newQuery()->insert($segmentRows);
        }
    }

    /**
     * The modal's time pickers submit am/pm display strings (e.g. "6:00am"),
     * matching the same widget used on the Schedule Next Step / Add Clinician
     * form; normalize to 24hr H:i:s for storage, same as that form's save flow.
     */
    private function normalizeTime(string $value): string
    {
        return date('H:i:s', strtotime($value));
    }
}
