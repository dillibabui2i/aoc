<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MoveRolesOfCurrentUsersToUsersHasRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $users = DB::table('users')->select('id','role')->get();
        $insertArr = [];
        foreach ($users as $key => $value) {
            $insertArr[] = ['user_id'=>$value->id,'role_id'=>$value->role];
        }
        DB::table('users_has_roles')->insert($insertArr);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
