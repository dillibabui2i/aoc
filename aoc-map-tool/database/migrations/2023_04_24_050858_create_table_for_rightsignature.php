<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableForRightsignature extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rightsignature_documents', function (Blueprint $table) {
            $table->id();
            $table->string('entity_id',200)->nullable();
            $table->string('entity_type',200)->nullable();
            $table->string('document_id',200)->nullable();
            $table->string('document_filename',200)->nullable();
            $table->string('document_link',200)->nullable();            
            $table->datetime('send_date')->nullable();
            $table->datetime('signed_date')->nullable();
            $table->string('status',200)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_for_rightsignature');
    }
}
