<?php

namespace App\Criteria;

use Illuminate\Database\Query\Builder;

interface CriteriaInterface
{
    public function apply(Builder $query): Builder;
}
