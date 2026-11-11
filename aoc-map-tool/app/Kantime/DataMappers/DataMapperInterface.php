<?php

namespace App\Kantime\DataMappers;

use App\Kantime\Domain\DataItem;

interface DataMapperInterface
{
    public function fromDaasRow(array $item) : DataItem;

    public function toDbRow(DataItem $item): array;
}
