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
        Schema::table('aoc_documents', function (Blueprint $table) {
            $table->text('document_url')->after("document_link")->nullable();
            $table->integer('sender_id')->after("document_url")->nullable();
            $table->text('extra_data')->after("sender_id")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aoc_documents', function (Blueprint $table) {
            //
        });
    }
};
