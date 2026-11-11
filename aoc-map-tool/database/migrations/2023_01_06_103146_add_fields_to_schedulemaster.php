<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToSchedulemaster extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at'); 
            $table->integer('is_updated');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('schedulemaster', function (Blueprint $table) {
            //
        });
    }
}
