<?php

namespace App\VBC\Models\VaccineDose;

use App\VBC\Enum\VaccineDose\VaccineDoseStatusEnum;
use App\VBC\Enum\VaccineDose\VaccineDoseStatusReasonEnum;
use App\VBC\Models\ImmunizationTracker\ImmunizationTrackerModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $immunization_tracker_id
 * @property int|null $sequence_number
 * @property Carbon $due_date
 * @property Carbon $earliest_date
 * @property Carbon|null $administered_date
 * @property VaccineDoseStatusEnum $status
 * @property VaccineDoseStatusReasonEnum|null $status_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read ImmunizationTrackerModel $immunizationTracker
 */
class VaccineDoseModel extends Model
{
    use HasFactory;
    use SoftDeletes;

    public $table = 'vaccine_doses';
    protected $fillable = [
        'immunization_tracker_id',
        'sequence_number',
        'due_date',
        'earliest_date',
        'administered_date',
        'status',
        'status_reason',
    ];
    protected $casts = [
        'sequence_number' => 'integer',
        'due_date' => 'datetime',
        'earliest_date' => 'datetime',
        'administered_date' => 'datetime',
        'status' => VaccineDoseStatusEnum::class,
        'status_reason' => VaccineDoseStatusReasonEnum::class,
    ];

    public function immunizationTracker(): BelongsTo
    {
        return $this->belongsTo(ImmunizationTrackerModel::class, 'immunization_tracker_id', 'id');
    }
}
