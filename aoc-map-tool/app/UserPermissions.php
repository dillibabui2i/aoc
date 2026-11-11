<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserPermissions extends Model
{
    protected $table = 'user_has_permission';
    protected $fillable = ['permissions_id', 'user_id'];
}
