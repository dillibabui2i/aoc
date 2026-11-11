<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIntakeClientInMysqlPatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            //
            $table->boolean('ccc_intro_call')->default(0);
            $table->timestamp('ccc_intro_call_made_at')->nullable();
            $table->integer('ccc_intro_call_made_by')->default(0)->comment('links to users table');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            //
            $table->dropColumn('ccc_intro_call');
            $table->dropColumn('ccc_intro_call_made_at');
            $table->dropColumn('ccc_intro_call_made_by');
        });
    }
}
