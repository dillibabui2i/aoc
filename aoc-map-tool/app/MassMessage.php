<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MassMessage extends Model
{
    use HasFactory;
    protected $table = 'mass_message';
    protected $fillable = ['user_id', 'receivers','message','total_count'];
}
