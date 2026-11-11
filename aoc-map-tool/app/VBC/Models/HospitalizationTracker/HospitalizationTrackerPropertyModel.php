<?php

namespace App\VBC\Models\HospitalizationTracker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $hospitalization_tracker_id
 * @property int $definition_id
 * @property mixed $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read HospitalizationTrackerModel $hospitalizationTracker
 * @property-read HospitalizationTrackerCustomPropertyDefinitionModel $definition
 */
class HospitalizationTrackerPropertyModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_properties';
    protected $fillable = [
        'hospitalization_tracker_id',
        'definition_id',
        'value',
    ];
    protected $casts = [
        'value' => 'array',
    ];

    public function hospitalizationTracker(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerCustomPropertyDefinitionModel::class, 'definition_id', 'id');
    }
}
