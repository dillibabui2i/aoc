<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // trach_vent_class_id is superseded by trach_vent_class_occurrence_id; nothing in the
        // codebase reads or writes it anymore, and it's NOT NULL with no default, which blocks
        // every insert once callers stop populating it. The preceding backfill migration has
        // already copied every request's link over to trach_vent_class_occurrence_id.
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->dropColumn('trach_vent_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->integer('trach_vent_class_id')->nullable()->after('id')->comment('primary key of trach_vent_classes');
        });
    }
};
