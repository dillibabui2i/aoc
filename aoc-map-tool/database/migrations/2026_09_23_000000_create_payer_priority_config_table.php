<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePayerPriorityConfigTable extends Migration
{
    public function up(): void
    {
        Schema::create('payer_priority_config', function (Blueprint $table) {
            $table->id();
            $table->string('state');
            $table->string('state_abbr');
            $table->integer('rank');
            $table->string('payer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payer_priority_config');
    }
}
