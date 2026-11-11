<?php

namespace App\Kantime\Domain;

class IntegerId
{
    public function __construct(protected readonly int $id)
    {
    }

    public function getValue() : int
    {
        return $this->id;
    }
}
