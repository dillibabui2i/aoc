<?php

namespace App\Models;

use App\Enums\Adjustments\AdjustmentTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Adjustment extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'adjustments';
    protected $guarded = [];

    CONST REGION_AR_DIRECTOR_ROLE_ID = 57;

    public function level_1(): MorphTo
    {
        return $this->morphTo('level_1');
    }

    public function level_2(): MorphTo
    {
        return $this->morphTo('level_2');
    }

    public function level_3(): MorphTo
    {
        return $this->morphTo('level_3');
    }

    public function getLevel1DisplayNameAttribute(): ?string
    {
        if (!$this->level_1) {
            return null;
        }

        if ($this->level_1_type === AdjustmentTypeEnum::ROLE->value &&
            (int)$this->level_1_id === self::REGION_AR_DIRECTOR_ROLE_ID) {
            return 'Region AR Director';
        }

        return $this->level_1->name;
    }

    public function getLevel2DisplayNameAttribute(): ?string
    {
        if (!$this->level_2) {
            return null;
        }

        if ($this->level_2_type === AdjustmentTypeEnum::ROLE->value &&
            (int)$this->level_2_id === self::REGION_AR_DIRECTOR_ROLE_ID) {
            return 'Region AR Director';
        }
        return $this->level_2->name;
    }

    public function getLevel3DisplayNameAttribute(): ?string
    {
        if (!$this->level_3) {
            return null;
        }

        if ($this->level_3_type === AdjustmentTypeEnum::ROLE->value &&
            (int)$this->level_3_id === self::REGION_AR_DIRECTOR_ROLE_ID) {
            return 'Region AR Director';
        }

        return $this->level_3->name;
    }
}
