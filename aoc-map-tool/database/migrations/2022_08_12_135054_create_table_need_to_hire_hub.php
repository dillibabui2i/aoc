<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableNeedToHireHub extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Hub', function (Blueprint $table) {
            $table->id();
			$table->integer('HubID')->nullable();
			$table->string('State', 55)->default('');
			$table->string('ShortState', 2)->default('');
			$table->string('City', 255)->default('');
			$table->string('HUB', 255)->default('');
			$table->string('Branch', 255)->default('');
			$table->string('FacilityID')->default('');
			$table->float('MilesFromHub')->nullable();
			$table->integer('Population')->nullable();
			$table->longText('Notes')->default('');
            $table->integer('CreatedOn')->nullable();
			$table->integer('CreatedBy')->nullable();
            $table->timestamp('ModifiedOn')->useCurrent();
            $table->integer('ModifiedBy')->nullable();
			$table->boolean('is_deleted')->default(0);
			$table->boolean('is_updated')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('NeedToHire_Hub');
    }
}
