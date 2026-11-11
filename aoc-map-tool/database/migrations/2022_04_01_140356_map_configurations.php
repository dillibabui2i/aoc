<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MapConfigurations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map_configurations', function (Blueprint $table) {
            $table->id('map_id');
            $table->string('map_tablename', 255)->collation('utf8mb4_unicode_ci')->nullable()->default('');
            $table->string('map_name', 255)->nullable()->default('');
            $table->string('map_category', 255)->nullable()->default('');
            $table->string('map_type', 255)->nullable()->default('');
            $table->text('map_filters')->nullable();
            $table->text('enable_address_filter')->nullable();
            $table->text('map_search_radius')->nullable();
            $table->text('map_search_fields')->nullable();
            $table->text('fields_on_map')->nullable();
            $table->text('fields_datatable')->nullable();
            $table->text('fields_datatable_detail')->nullable();
            $table->text('default_orderby_datatable')->nullable();
            $table->text('latlong_fields')->nullable();
            $table->timestamp('created_at')->useCurrent();
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
