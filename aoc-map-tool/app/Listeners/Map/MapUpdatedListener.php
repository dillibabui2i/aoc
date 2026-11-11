<?php

namespace App\Listeners\Map;

use App\Events\Map\MapUpdatedEvent;
use App\Map;

class MapUpdatedListener
{

    public function handle(MapUpdatedEvent $event): void
    {
        Map::query()
            ->where('map_tablename', $event->mapName)
            ->update([
                'last_uploaded_at' => now(),
                'last_uploaded_by_user_id' => $event->userId ?? 1, // 1 = AppBot
            ]);
    }

}
