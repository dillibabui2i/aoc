<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('sick_plan_items', function (Blueprint $table) {
            $table->boolean('completed')->default(false)->after('catalog_id');
            $table->unsignedBigInteger('completed_by_id')->nullable()->after('completed');
            $table->unsignedBigInteger('author_id')->nullable()->after('completed_by_id');
            $table->text('notes')->nullable()->after('author_id');
            $table->timestamps();

            $table->foreign('completed_by_id')->references('id')->on('users');
            $table->foreign('author_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('sick_plan_items', function (Blueprint $table) {
            $table->dropForeign(['completed_by_id']);
            $table->dropForeign(['author_id']);
            $table->dropColumn(['completed', 'completed_by_id', 'author_id', 'notes', 'created_at', 'updated_at']);
        });
    }
};
