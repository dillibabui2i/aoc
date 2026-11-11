<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('external_user_id')
                ->constrained('external_users')
                ->cascadeOnDelete();

            $table->foreignId('consent_template_id')
                ->constrained('consent_templates')
                ->cascadeOnDelete();

            $table->string('consent_version', 20);
            $table->dateTime('accepted_at');
            $table->string('source', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('user_consents', function (Blueprint $table) {
            $table->dropForeign(['external_user_id']);
            $table->dropForeign(['consent_template_id']);
        });

        Schema::dropIfExists('user_consents');
    }
};
