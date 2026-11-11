<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->enum('repeat_every', ['day', 'week', 'month'])->nullable()->after('class_time');
            $table->string('repeat_on')->nullable()->after('repeat_every')->comment('comma-separated ISO weekday numbers (1=Monday..7=Sunday), only used when repeat_every=day');
            $table->date('repeat_until')->nullable()->after('repeat_on');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->dropColumn(['repeat_every', 'repeat_on', 'repeat_until']);
        });
    }
};
