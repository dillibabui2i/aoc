<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->time('end_time')->nullable()->after('class_time');
        });

        Schema::table('trach_vent_class_occurrences', function (Blueprint $table) {
            // Nullable override for this occurrence only; null means "inherit from trach_vent_classes".
            $table->time('end_time')->nullable()->after('class_time');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_class_occurrences', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });

        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->dropColumn('end_time');
        });
    }
};
