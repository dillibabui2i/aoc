<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMssqlAuthorizationsmasterDaasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('authorizations', function (Blueprint $table) {
            $table->id();
			$table->integer('auth_id')->nullable();
            $table->integer('aoc_patient_id')->nullable();
			$table->integer('aoc_facility_id')->nullable();
            $table->integer('payer_id')->nullable();
            $table->string('number',200)->nullable();
			$table->timestamp('start_date')->nullable();
			$table->timestamp('end_date')->nullable();			
            $table->boolean('is_visitbased')->nullable();
            $table->double('totalunits',7,2)->nullable();
            $table->integer('visit_count')->nullable();
            $table->integer('service_code_id')->nullable();
            $table->string('discipline_name')->nullable();
			$table->double('unusedunits',7,2)->nullable();
			$table->double('weekly_limit',7,2)->nullable();
			$table->double('monthly_limit',7,2)->nullable();
			$table->double('daily_limit',7,2)->nullable();
			$table->boolean('is_auto')->nullable();
            $table->integer('agency_id')->nullable();
			$table->string('payer_allocationorder', 200)->nullable();
			$table->boolean('is_hourlybased')->nullable();
			$table->timestamp('sf_created_at')->nullable();
			$table->timestamp('sf_updated_at')->nullable();
            $table->integer('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->boolean('is_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('authorizations');
    }
}
