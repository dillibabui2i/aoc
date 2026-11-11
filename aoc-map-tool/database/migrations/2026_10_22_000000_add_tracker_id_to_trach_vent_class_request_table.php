<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            // Recruiter Tracker the registration was documented on; null when scheduled from the Class Details pop-up.
            $table->unsignedBigInteger('tracker_id')->nullable()->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_class_request', function (Blueprint $table) {
            $table->dropColumn('tracker_id');
        });
    }
};
