<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'regions';
    protected $guarded = [];
    public $timestamps = false;
}
