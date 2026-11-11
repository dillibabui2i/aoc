<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Calllegs extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'call_logs_8x8';
    protected $guarded = [];
    public $timestamps = false;
}
