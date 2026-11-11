<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LoginRestriction extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'login_restrictions';
}
