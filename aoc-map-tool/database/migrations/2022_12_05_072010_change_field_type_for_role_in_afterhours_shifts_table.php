<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldTypeForRoleInAfterhoursShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            //
            $table->text('roles_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('afterhours_shifts', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('roles_id')->change();
        });
    }
}
