<?php

namespace App\Models;

use App\Enums\Adjustments\AdjustmentEventTypeEnum;
use App\Enums\Adjustments\AdjustmentStageEnum;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ClaimAdjustmentHistory extends Model
{

    protected $table = 'claim_adjustment_histories';

    protected $guarded = [];

    protected $casts = [
        'stage' => AdjustmentStageEnum::class,
        'event_type' => AdjustmentEventTypeEnum::class,
    ];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(ClaimsMaster::class, 'claim_master_id');
    }

    public function actedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acted_by_user_id');
    }

    public function assignee(): MorphTo
    {
        return $this->morphTo();
    }

}
