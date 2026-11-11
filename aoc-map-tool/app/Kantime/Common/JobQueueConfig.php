<?php

namespace App\Kantime\Common;

class JobQueueConfig
{
    public function __construct(
        public readonly string $daasQueue,
        public readonly int $overlapTtlSeconds,
        public readonly int $defaultLimit,
        public readonly string $daasConnection,
    ) {}
}
