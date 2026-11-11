<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RolesPermissions extends Model
{
    protected $table = 'role_has_permissions';
    protected $fillable = ['permissions_id', 'roles_id'];
}
