<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToMssqlPatients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mssql_patients', function (Blueprint $table) {
            $table->string('TreatmentAddressName',255)->nullable();
            $table->string('TreatmentAddress1',255)->nullable();
            $table->string('TreatmentAddress2',255)->nullable();
            $table->string('TreatmentAddressCity',255)->nullable();
            $table->string('TreatmentAddressState',255)->nullable();
            $table->string('TreatmentAddressEmail',255)->nullable();
            $table->string('TreatmentAddressZIPCode',255)->nullable();
            $table->string('TreatmentAddressTelePhone',255)->nullable();
            $table->string('TreatmentAddressCellPhone',255)->nullable();
            $table->string('CF_Schooldistrict',255)->nullable();
            $table->string('CF_SchoolName',255)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
