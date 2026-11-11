<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up()
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->foreignId('parent_id')->after('map_id')->nullable()->constrained('map_configurations', 'map_id');
            $table->string('pin_icon', 255)->nullable();
            $table->string('pin_color', 255)->nullable();
            $table->unsignedSmallInteger('layer_ring_radius')->nullable();
            $table->foreignId('ring_layer_id')->nullable()->constrained('map_configurations', 'map_id');
            $table->boolean('show_rings_by_default')->default(false);
        });
    }

    public function down()
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ring_layer_id');
            $table->dropConstrainedForeignId('parent_id');

            $table->dropColumn([
                'pin_icon',
                'pin_color',
                'layer_ring_radius',
                'show_rings_by_default',
            ]);
        });
    }

};
