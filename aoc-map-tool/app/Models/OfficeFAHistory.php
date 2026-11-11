<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficeFAHistory extends Model
{
    public $table = 'office_fa_history';
    public $fillable = [
        'email',
    ];
}
