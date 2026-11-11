<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('trach_vent_class_occurrences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id')->comment('primary key of trach_vent_classes');
            $table->date('class_date');
            $table->index(['class_id', 'class_date']);
            // Nullable overrides for this occurrence only; null means "inherit from trach_vent_classes".
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->integer('capacity')->nullable();
            $table->text('notes')->nullable();
            $table->time('class_time')->nullable();
            $table->string('class_type')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));

            $table->foreign('class_id')->references('id')->on('trach_vent_classes')->onDelete('cascade');
            $table->foreign('instructor_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trach_vent_class_occurrences');
    }
};
