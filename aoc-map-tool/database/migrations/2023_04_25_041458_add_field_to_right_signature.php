<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldToRightSignature extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rightsignature_documents', function (Blueprint $table) {
            $table->text('sending_request_id')->nullable();
            $table->text('document_id')->nullable()->change();
            $table->text('document_link')->nullable()->change();
            $table->text('document_filename')->nullable()->change();
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('right_signature', function (Blueprint $table) {
            //
        });
    }
}
