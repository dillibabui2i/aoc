<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToRightsignaturedocument extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rightsignature_documents', function (Blueprint $table) {
            $table->text('document_type')->nullable()->after("entity_type");
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rightsignaturedocument', function (Blueprint $table) {
            //
        });
    }
}
