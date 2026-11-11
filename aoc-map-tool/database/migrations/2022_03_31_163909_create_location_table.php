<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('location', function (Blueprint $table) {
            $table->id();
            $table->string('address1', 255)->default('')->collation('utf8_general_ci');
            $table->string('address2', 255)->default('')->collation('utf8_general_ci');
            $table->string('address3', 255)->default('')->collation('utf8_general_ci');
            $table->string('city', 255)->default('')->collation('utf8_general_ci');
            $table->string('state', 255)->default('')->collation('utf8_general_ci');
            $table->string('country', 150)->default('')->collation('utf8_general_ci');
            $table->integer('zipcode')->nullable();
            $table->decimal('lat', 9, 7)->nullable();
            $table->decimal('lng', 9, 7)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('address1', 'address1');
            $table->index('address2', 'address2');
            $table->index('address3', 'address3');
            $table->index('city', 'city');
            $table->index('state', 'state');
            $table->index('country', 'country');
            $table->index('zipcode', 'zipcode');
            $table->index('lat', 'lat');
            $table->index('lng', 'lng');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('location');
    }
}
