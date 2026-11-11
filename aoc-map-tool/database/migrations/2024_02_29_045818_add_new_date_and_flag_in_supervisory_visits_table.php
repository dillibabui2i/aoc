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
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->boolean('visit_not_matched')->default(0);
            $table->date('form_sup_visit_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('supervisory_visits', function (Blueprint $table) {
            $table->dropColumn('visit_not_matched');
            $table->dropColumn('form_sup_visit_date');
        });
    }
};
