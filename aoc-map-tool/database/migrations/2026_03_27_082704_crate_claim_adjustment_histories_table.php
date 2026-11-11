<?php

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\Adjustments\AdjustmentStageEnum;
use App\Enums\Adjustments\AdjustmentTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('claim_adjustment_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('claim_master_id');
            $table->enum('assignee_type', [
                AdjustmentTypeEnum::ROLE->value,
                AdjustmentTypeEnum::USER->value
            ]);
            $table->unsignedBigInteger('assignee_id');
            $table->enum('stage', [
                AdjustmentStageEnum::LEVEL_1->value,
                AdjustmentStageEnum::LEVEL_2->value,
                AdjustmentStageEnum::LEVEL_3->value,
                AdjustmentStageEnum::OPERATIONS->value,
                AdjustmentStageEnum::AR_SUPERVISOR->value,
            ]);
            $table->enum('event_type',[
                AdjustmentEventTypeEnum::ASSIGNED->value,
                AdjustmentEventTypeEnum::APPROVED->value,
                AdjustmentEventTypeEnum::REJECTED->value,
                AdjustmentEventTypeEnum::COMPLETED->value,
            ]);
            $table->unsignedBigInteger('acted_by_user_id')->nullable();
            $table->timestamps();

            $table->index('claim_master_id');
            $table->index(['claim_master_id', 'created_at']);
            $table->index(['claim_master_id', 'event_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('claim_adjustment_histories');
    }
};
