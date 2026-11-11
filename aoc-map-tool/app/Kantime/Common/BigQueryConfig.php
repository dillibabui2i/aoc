<?php

namespace App\Kantime\Common;

class BigQueryConfig
{
    public function __construct(
        public readonly string $projectId,
        public readonly string $dataset,
    ) {}

    public function projectId(): string
    {
        return $this->projectId;
    }
    public function dataset(): string
    {
        return $this->dataset;
    }
}
