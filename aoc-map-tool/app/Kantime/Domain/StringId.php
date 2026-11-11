<?php

namespace App\Kantime\Domain;

use Ramsey\Uuid\Type\Integer;

class StringId
{
    public function __construct(private readonly string $id)
    {
    }

    protected function getValue() : string
    {
        return $this->id;
    }
}
