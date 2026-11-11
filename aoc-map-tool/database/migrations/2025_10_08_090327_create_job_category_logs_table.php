<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('job_category_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_category_id')->constrained('job_categories')->onDelete('cascade');
            $table->string('action');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->bigInteger('user_id')->nullable();
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
        Schema::table('job_category_logs', function (Blueprint $table) {
            $table->dropForeign(['job_category_id']);
        });

        Schema::dropIfExists('job_category_logs');
    }
};
