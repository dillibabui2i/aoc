<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->unsignedBigInteger('trach_vent_class_occurrence_id')->nullable()->after('trach_vent_class_id')->comment('primary key of trach_vent_class_occurrences');
            $table->foreign('trach_vent_class_occurrence_id')->references('id')->on('trach_vent_class_occurrences')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->dropForeign(['trach_vent_class_occurrence_id']);
            $table->dropColumn('trach_vent_class_occurrence_id');
        });
    }
};
