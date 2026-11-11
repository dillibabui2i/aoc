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
        Schema::table('journal_notes', function (Blueprint $table) {
            $table->datetime('note_cleared_date')->nullable();
            $table->integer('note_cleared_by')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journal_notes', function (Blueprint $table) {
            $table->dropColumn('note_cleared_date');
            $table->dropColumn('note_cleared_by');
        });
    }
};
