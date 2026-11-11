<?php

namespace App\VBC\Models\HospitalizationTracker;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $hospitalization_tracker_id
 * @property int $training_type_id
 * @property int $training_source_id
 * @property bool $is_completed
 * @property int|null $completed_by_id
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read HospitalizationTrackerModel $hospitalizationTracker
 * @property-read HospitalizationTrackerTrainingTypeModel|null $type
 * @property-read HospitalizationTrackerTrainingSourceModel|null $source
 * @property-read User|null $completedBy
 */
class HospitalizationTrackerTrainingModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_trainings';
    protected $fillable = [
        'hospitalization_tracker_id',
        'training_type_id',
        'training_source_id',
        'is_completed',
        'completed_by_id',
        'completed_at',
        'notes',
    ];
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function hospitalizationTracker(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerTrainingTypeModel::class, 'training_type_id', 'id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerTrainingSourceModel::class, 'training_source_id', 'id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id', 'id');
    }
}
