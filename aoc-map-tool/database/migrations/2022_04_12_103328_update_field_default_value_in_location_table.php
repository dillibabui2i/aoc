<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateFieldDefaultValueInLocationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('location', function (Blueprint $table) {
            //
            $table->string('address1', 255)->nullable()->change();
            $table->string('address2', 255)->nullable()->change();
            $table->string('address3', 255)->nullable()->change();
            $table->string('city', 255)->nullable()->change();
            $table->string('state', 255)->nullable()->change();
            $table->string('country', 150)->nullable()->change();

            //$table->fulltext(['address1','city','state','country'],'location_fulltext');
        });
        DB::statement('ALTER TABLE `location` ADD FULLTEXT `location_address_fulltext_index` (`address1`, `city`, `state`, `country`);');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('location', function (Blueprint $table) {
            //
            $table->string('address1', 255)->default('')->nullable(false)->change();
            $table->string('address2', 255)->default('')->nullable(false)->change();
            $table->string('address3', 255)->default('')->nullable(false)->change();
            $table->string('city', 255)->default('')->nullable(false)->change();
            $table->string('state', 255)->default('')->nullable(false)->change();
            $table->string('country', 150)->default('')->nullable(false)->change();

            $table->dropIndex(['address_fulltext']);
        });
    }
}
