<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->timestamp('last_uploaded_at')->nullable()->after('description');
            $table->unsignedBigInteger('last_uploaded_by_user_id')->nullable()->after('last_uploaded_at');

            $table->index('last_uploaded_at');
            $table->index('last_uploaded_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('map_configurations', function (Blueprint $table) {
            $table->dropIndex(['last_uploaded_at']);
            $table->dropIndex(['last_uploaded_by_user_id']);

            $table->dropColumn([
                'last_uploaded_at',
                'last_uploaded_by_user_id',
            ]);
        });
    }
};
