<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvailabilityCapacity extends Model
{
    protected $table = 'ccr_availability_capacity';

    protected $fillable = [
        'aoc_facility_id',
        'day_of_week',
        'start_time',
        'end_time',
        'capacity_per_hour',
        'created_by',
        'updated_by',
    ];
}
