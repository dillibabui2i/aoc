<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MssqlPatients extends Model
{
    use HasFactory;
    protected $table = 'mssql_patients';

    protected static function getPatientDetails($id, $fields)
    {
        return self::select($fields)->where('id', $id)->first();
    }
    protected static function getUserIdByBranchAndRole($branchid , $role)
    {
        $userIds = [];
        if (!empty($branchid)) {
            $users = User::select('users.id', 'users.name')
                ->leftJoin("users_has_roles","users.id","=","users_has_roles.user_id")
                ->leftJoin("roles","users_has_roles.role_id","=","roles.id")
                ->where("roles.name",$role)->where("users.status",1)
                ->whereRaw("find_in_set(".$branchid.", users.branchid)")->get()->toArray();
            if (!empty($users)) {
                foreach($users as $user) {
                    $userIds[] = $user['id'];
                }
                return $userIds;
            } else {
                return $userIds;
            }
        } else {
            return $userIds;
        }
    }
}
