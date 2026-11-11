<?php

namespace App\VBC\Models\ImmunizationRecommendedFor;

use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $recommendation_id
 * @property string $display_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ImmunizationRecommendationModel $recommendation
 */
class ImmunizationRecommendedForModel extends Model
{
    use SoftDeletes;

    public $table = 'immunization_recommended_for';
    protected $fillable = [
        'recommendation_id',
        'display_name',
    ];

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(ImmunizationRecommendationModel::class, 'recommendation_id', 'id');
    }
}
