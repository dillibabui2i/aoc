<?php

namespace App\VBC\Models\ImmunizationDoseAge;

use App\VBC\Enum\ImmunizationDoseAge\ImmunizationDoseAgeUnitEnum;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $recommendation_id
 * @property int|null $from_age 0 = Birth
 * @property ImmunizationDoseAgeUnitEnum|null $from_age_unit
 * @property int|null $to_age
 * @property ImmunizationDoseAgeUnitEnum|null $to_age_unit
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ImmunizationRecommendationModel $recommendation
 */
class ImmunizationDoseAgeModel extends Model
{
    use SoftDeletes;

    public $table = 'immunization_dose_ages';
    protected $fillable = [
        'recommendation_id',
        'from_age',
        'from_age_unit',
        'to_age',
        'to_age_unit',
    ];
    protected $casts = [
        'from_age' => 'integer',
        'from_age_unit' => ImmunizationDoseAgeUnitEnum::class,
        'to_age' => 'integer',
        'to_age_unit' => ImmunizationDoseAgeUnitEnum::class,
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(ImmunizationRecommendationModel::class, 'recommendation_id', 'id');
    }
}
