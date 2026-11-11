<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::rename('hospitalization_tracker_training_sourcess', 'hospitalization_tracker_training_sources');
    }

    public function down(): void
    {
        Schema::rename('hospitalization_tracker_training_sources', 'hospitalization_tracker_training_sourcess');
    }
};
