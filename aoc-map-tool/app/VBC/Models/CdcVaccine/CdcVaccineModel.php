<?php

namespace App\VBC\Models\CdcVaccine;

use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use App\VBC\Models\ImmunizationTracker\ImmunizationTrackerModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $cdc_full_name
 * @property string|null $cdc_short_description
 * @property string|null $cdc_status
 * @property int $cvx_code
 * @property int|null $cvx_group_code
 * @property Carbon|null $cdc_last_updated
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read CdcVaccineGroupModel|null $group
 * @property-read Collection<int, ImmunizationRecommendationModel> $recommendations
 * @property-read Collection<int, ImmunizationTrackerModel> $trackers
 */
class CdcVaccineModel extends Model
{
    use SoftDeletes;

    public $table = 'cdc_vaccines';
    protected $fillable = [
        'cdc_full_name',
        'cdc_short_description',
        'cdc_status',
        'cvx_code',
        'cvx_group_code',
        'cdc_last_updated',
    ];
    protected $casts = [
        'cvx_code' => 'integer',
        'cvx_group_code' => 'integer',
        'cdc_last_updated' => 'datetime',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(CdcVaccineGroupModel::class, 'cvx_group_code', 'cvx_group_code');
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(ImmunizationRecommendationModel::class, 'vaccine_cvx_code', 'cvx_code');
    }

    public function trackers(): HasMany
    {
        return $this->hasMany(ImmunizationTrackerModel::class, 'vaccine_cvx_code', 'cvx_code');
    }
}
