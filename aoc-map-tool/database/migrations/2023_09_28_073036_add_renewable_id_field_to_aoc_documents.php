<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRenewableIdFieldToAocDocuments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aoc_documents', function (Blueprint $table) {
            $table->integer('associate_id')->nullable()->after("evaluation_id")->comment('field for any third table relation will be used along with document_type');
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
}
