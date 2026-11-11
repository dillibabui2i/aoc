<?php

namespace App\DTO\Client;

readonly class ScheduleDropdownDTO
{
    /**
     * @param string $term
     */
    public function __construct(private readonly string $term)
    {}

    /**
     * @return string
     */
    public function getTerm(): string
    {
        return $this->term;
    }
}
