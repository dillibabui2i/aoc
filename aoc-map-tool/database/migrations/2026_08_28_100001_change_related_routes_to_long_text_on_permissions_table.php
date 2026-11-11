<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * related_routes was varchar(255) and permission groups have outgrown it — the "Intensive Case
 * Management" list hit exactly 255 chars and MariaDB silently truncated the tail, dropping real routes
 * (and with them access to the endpoints they gate). LONGTEXT removes the ceiling for good, since these
 * lists only ever grow as more routes are added to a permission.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->longText('related_routes')->change();
        });
    }

    public function down(): void
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->string('related_routes', 255)->change();
        });
    }
};
