<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToPaycomTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('paycom', function (Blueprint $table) {
            $table->boolean('id_fixed')->default(0);
            $table->integer('id_fixed_by')->default(0);
            $table->datetime('id_fixed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('paycom', function (Blueprint $table) {
            $table->dropColumn('id_fixed');
            $table->dropColumn('id_fixed_by');
            $table->dropColumn('id_fixed_at');
        });
    }
}
