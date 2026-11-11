<?php

namespace App\Events\Map;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MapUpdatedEvent
{

    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly string $mapName,
        public readonly ?int $userId = null,
    ) {}

}
