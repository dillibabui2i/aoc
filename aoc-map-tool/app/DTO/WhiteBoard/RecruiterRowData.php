<?php

namespace App\DTO\WhiteBoard;

readonly class RecruiterRowData
{
    /**
     * @param int $recruiterId
     * @param string $title
     * @param int $weekly_start_goal
     * @param array $daily
     * @param array $wtd
     */
    public function __construct(
        public int $recruiterId,
        public string $title,
        public int $weekly_start_goal,
        public array $daily,
        public array $wtd
    ) {}
}
