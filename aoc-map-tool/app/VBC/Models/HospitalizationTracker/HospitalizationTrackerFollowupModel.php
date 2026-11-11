<?php

namespace App\VBC\Models\HospitalizationTracker;

use App\MssqlPatients;
use App\User;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $hospitalization_tracker_id
 * @property int|null $patient_id
 * @property int|null $assigned_to_id
 * @property int|null $requested_by_id
 * @property HospitalizationTrackerFollowupStatusEnum $status
 * @property Carbon|null $scheduled_at
 * @property Carbon|null $attempted_at
 * @property int|null $attempted_by_id
 * @property Carbon|null $completed_at
 * @property int|null $completed_by_id
 * @property string|null $status_note
 * @property string|null $follow_ups_updates
 * @property string|null $needs
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read HospitalizationTrackerModel|null $hospitalizationTracker
 * @property-read Collection<int, HospitalizationTrackerModel> $patientHospitalizationTrackers
 * @property-read MssqlPatients|null $patient
 * @property-read User|null $assignedTo
 * @property-read User|null $requestedBy
 * @property-read User|null $completedBy
 * @property-read User|null $attemptedBy
 */
class HospitalizationTrackerFollowupModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_followups';
    protected $fillable = [
        'hospitalization_tracker_id',
        'patient_id',
        'assigned_to_id',
        'requested_by_id',
        'status',
        'scheduled_at',
        'attempted_at',
        'attempted_by_id',
        'completed_at',
        'completed_by_id',
        'status_note',
        'follow_ups_updates',
        'needs',
        'notes',
    ];
    protected $casts = [
        'status' => HospitalizationTrackerFollowupStatusEnum::class,
        'scheduled_at' => 'datetime',
        'attempted_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function hospitalizationTracker(): BelongsTo
    {
        return $this->belongsTo(HospitalizationTrackerModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function patientHospitalizationTrackers(): HasManyThrough
    {
        return $this->hasManyThrough(
            HospitalizationTrackerModel::class,
            EncounterModel::class,
            'patient_id',   // FK on encounters
            'encounter_id', // FK on hospitalization_trackers
            'patient_id',   // local key on hospitalization_tracker_followups
            'id'            // local key on encounters
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_id', 'id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id', 'id');
    }

    public function attemptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attempted_by_id', 'id');
    }
}
