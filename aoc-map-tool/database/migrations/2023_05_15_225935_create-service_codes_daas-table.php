<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateServiceCodesDaasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('service_codes_daas', function (Blueprint $table) {
            $table->id();
            $table->integer('service_id')->nullable();
            $table->integer('parent_serviceid')->nullable();
            $table->integer('payerid')->nullable();
			$table->integer('agency_id')->nullable();
			$table->string('type',200)->nullable();
            $table->string('name',200)->nullable();
            $table->string('code',200)->nullable();
            $table->string('paycode',200)->nullable();
            $table->integer('group_id')->nullable();
			$table->integer('form_id')->nullable();
			$table->integer('sf_created_by')->nullable();
            $table->boolean('active')->nullable()->default(0);
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
        Schema::dropIfExists('service_codes_daas');
    }
}
