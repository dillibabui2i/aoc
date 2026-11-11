<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class FieldEvaluation extends Model
{
    use HasFactory;
    protected $fillable = [
        'aoc_provider_id',
        'manager',
        'status',
        'item1_score',
        'item2_score',
        'item3_score',
        'item4_score',
        'item5_score',
        'item6_score',
        'comments'
    ];

    /*public static function booted() {

        parent::booted();

        static::addGlobalScope('exclude_disabled_user', function (Builder $builder) {
			$builder
            ->leftJoin('mssql_providers as mp', 'field_evaluations.aoc_provider_id', '=', 'mp.id')
            ->leftJoin('users as us' , 'mp.AOC_Email', '=', 'us.email')
            ->where('us.status' , '1');
		});
    }*/
}
