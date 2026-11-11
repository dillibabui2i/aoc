<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_compensation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->unsignedTinyInteger('version'); // 0 = original (clinical manager), 1 = final (recruiter)
            $table->foreignId('offer_field_id')->nullable()->constrained('offer_fields')->nullOnDelete();
            $table->string('custom_label')->nullable(); // free-text label when offer_field is the 'other' sentinel
            $table->decimal('amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['offer_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_compensation_items');
    }
};
