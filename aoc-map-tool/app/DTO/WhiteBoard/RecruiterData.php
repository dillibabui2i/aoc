<?php

namespace App\DTO\WhiteBoard;

readonly class RecruiterData
{
    public function __construct(
        public int $id,
        public int $userId,
        public string $state,
        public string $name,
        public string $region,
        public string $territory,
        public int $weekly_start_goal,
        public array $daily,
        public array $wtd
    )
    {}
}
