<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->index(
                ['appSourceType', 'appSource'],
                'idx_candidates_app_source_type_source'
            );
        });
    }

    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropIndex('idx_candidates_app_source_type_source');
        });
    }
};
