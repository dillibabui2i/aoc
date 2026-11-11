<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->index(['aoc_provider_id', 'id'], 'idx_recruitertracker_provider_id_id');
        });
    }

    public function down(): void
    {
        Schema::table('mssql_recruitertracker', function (Blueprint $table) {
            $table->dropIndex('idx_recruitertracker_provider_id_id');
        });
    }
};
