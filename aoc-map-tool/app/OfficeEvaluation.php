<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class OfficeEvaluation extends Model
{
    use HasFactory;

    public static function booted() {

        parent::booted();

        static::addGlobalScope('exclude_disabled_user', function (Builder $builder) {
            $builder->whereRaw("(select users.status from users left join mssql_providers on users.email = mssql_providers.AOC_Email where mssql_providers.id = office_evaluations.aoc_provider_id and mssql_providers.is_deleted = 0 and users.status = 1) = 1");
		});
    }
}
