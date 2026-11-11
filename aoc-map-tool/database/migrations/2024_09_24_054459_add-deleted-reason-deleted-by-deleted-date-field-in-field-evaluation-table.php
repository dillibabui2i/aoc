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
        Schema::table('field_evaluations', function (Blueprint $table) {
            $table->text('deleted_reason')->nullable();
            $table->datetime('deleted_date')->nullable();
            $table->integer('deleted_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('field_evaluations', function (Blueprint $table) {
            $table->dropColumn('deleted_reason');
            $table->dropColumn('deleted_date');
            $table->dropColumn('deleted_by');
        });
    }
};
