<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\Adjustments\AdjustmentTypeEnum;


return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('category');
            $table->enum('level_1_type', [AdjustmentTypeEnum::ROLE->value, AdjustmentTypeEnum::USER->value])->nullable();
            $table->unsignedBigInteger('level_1_id')->nullable();
            $table->enum('level_2_type', [AdjustmentTypeEnum::ROLE->value, AdjustmentTypeEnum::USER->value])->nullable();
            $table->unsignedBigInteger('level_2_id')->nullable();
            $table->enum('level_3_type', [AdjustmentTypeEnum::ROLE->value, AdjustmentTypeEnum::USER->value])->nullable();
            $table->unsignedBigInteger('level_3_id')->nullable();
            $table->boolean('requires_ops_review');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('adjustments');
    }
};
