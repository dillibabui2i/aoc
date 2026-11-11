<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('client_emergency_contacts', function (Blueprint $table) {
            $table->string('contact_id')->nullable()->after('intake_id');
            $table->string('alternate_phone')->nullable()->after('telephone');
            $table->string('work_phone')->nullable()->after('alternate_phone');
        });
    }

    public function down(): void
    {
        Schema::table('client_emergency_contacts', function (Blueprint $table) {
            $table->dropColumn(['contact_id', 'alternate_phone', 'work_phone']);
        });
    }
};
