<?php

namespace App\Repositories\Events;

use DB;

class EventsWriteRepository
{
    private const TABLE = 'aoc_events';

    public function create(array $data): int
    {
        return DB::table(self::TABLE)->insertGetId($data);
    }

    public function updateByEventId(string $id, array $data): bool
    {
        return DB::table(self::TABLE)->where('event_id', $id)->update($data);
    }
}
