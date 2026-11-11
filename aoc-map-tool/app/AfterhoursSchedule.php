<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AfterhoursSchedule extends Model
{
    use HasFactory;
    protected $table = 'afterhours_schedule';
    protected $fillable = ['afterhours_shifts_id',
    'user_id',
    'start_date',
    'start_time',
    'end_date',
    'end_time',
    'created_by',
    'updated_by',
    'notification1',
    'notification2',
    'notification3',
    'notification4',
    'notification5'
    ];
}
