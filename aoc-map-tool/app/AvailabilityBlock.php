<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AvailabilityBlock extends Model
{
    protected $table = 'ccr_availability_block';

    protected $fillable = [
        'aoc_facility_id',
        'block_date',
        'start_time',
        'end_time',
        'note',
        'created_by',
        'updated_by',
    ];
}
