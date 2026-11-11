<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('cdc_vaccine_groups', function (Blueprint $table) {
            $table->id();
            $table->string('aoc_name')->nullable();
            $table->string('cdc_name')->nullable();
            $table->string('cdc_short_description')->nullable();
            $table->unsignedSmallInteger('cvx_group_code')->unique();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cdc_vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('cdc_full_name')->nullable();
            $table->string('cdc_short_description')->nullable();
            $table->string('cdc_status', 64)->nullable();
            $table->unsignedSmallInteger('cvx_code')->unique();
            $table->unsignedSmallInteger('cvx_group_code')->nullable();
            $table->dateTime('cdc_last_updated')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cvx_group_code', 'fk_cdc_vaccines_cvx_group_code')
                ->references('cvx_group_code')->on('cdc_vaccine_groups');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cdc_vaccines');
        Schema::dropIfExists('cdc_vaccine_groups');
    }
};
