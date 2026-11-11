<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->integer('intake_admission_visit')->nullable()->index();
            $table->integer('intake_first_billable_visit')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intake', function (Blueprint $table) {
            $table->dropColumn('intake_admission_visit');
            $table->dropColumn('intake_first_billable_visit');
        });
    }
};
