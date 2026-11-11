<?php

namespace App\VBC\Models\HospitalizationTracker;

use App\User;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerCareRequirementEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerClosingReasonEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerDeclinedProgramContactEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerIcmLevelEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerSourceEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;
use App\VBC\Models\HospitalizationTrackerContact\HospitalizationTrackerContactModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $encounter_id
 * @property int|null $author_id
 * @property HospitalizationTrackerStatusEnum $status
 * @property HospitalizationTrackerIcmLevelEnum|null $icm_level
 * @property HospitalizationTrackerSourceEnum|null $source
 * @property HospitalizationTrackerClosingReasonEnum|null $closing_reason
 * @property string|null $closing_reason_details
 * @property HospitalizationTrackerDeclinedProgramContactEnum $declined_program_contact
 * @property array<int, string>|null $aoc_care_requirements HospitalizationTrackerCareRequirementEnum values
 * @property int|null $assigned_to_id
 * @property int|null $sent_to_review_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read EncounterModel $encounter
 * @property-read User|null $author
 * @property-read User|null $assignedTo
 * @property-read User|null $sentToReviewBy
 * @property-read Collection<int, HospitalizationTrackerTrainingModel> $trainings
 * @property-read Collection<HospitalizationTrackerPropertyModel> $properties
 * @property-read Collection<HospitalizationTrackerFollowupModel> $followups
 * @property-read HospitalizationTrackerFollowupModel|null $latestFollowup
 * @property-read HospitalizationTrackerFollowupModel|null $currentFollowup
 * @property-read Collection<int, HospitalizationTrackerContactModel> $contacts
 * @property-read Collection<int, EncounterDiagnosisModel> $diagnoses
 */
class HospitalizationTrackerModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_trackers';
    protected $fillable = [
        'encounter_id',
        'author_id',
        'status',
        'icm_level',
        'source',
        'closing_reason',
        'closing_reason_details',
        'declined_program_contact',
        'aoc_care_requirements',
        'assigned_to_id',
        'sent_to_review_by_id',
    ];
    protected $casts = [
        'status' => HospitalizationTrackerStatusEnum::class,
        'icm_level' => HospitalizationTrackerIcmLevelEnum::class,
        'source' => HospitalizationTrackerSourceEnum::class,
        'closing_reason' => HospitalizationTrackerClosingReasonEnum::class,
        'declined_program_contact' => HospitalizationTrackerDeclinedProgramContactEnum::class,
        'aoc_care_requirements' => 'array',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(EncounterModel::class, 'encounter_id', 'id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }

    public function sentToReviewBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_to_review_by_id', 'id');
    }

    public function trainings(): HasMany
    {
        return $this->hasMany(HospitalizationTrackerTrainingModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function properties(): HasMany
    {
        return $this->hasMany(HospitalizationTrackerPropertyModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function followups(): HasMany
    {
        return $this->hasMany(HospitalizationTrackerFollowupModel::class, 'hospitalization_tracker_id', 'id');
    }

    public function latestFollowup(): HasOne
    {
        return $this->hasOne(HospitalizationTrackerFollowupModel::class, 'hospitalization_tracker_id', 'id')
            ->ofMany('completed_at', 'max');
    }

    /**
     * The most recently created follow-up, whatever its status — unlike latestFollowup(), which only
     * ever resolves to a Completed one. Backs the Triage "ED Visits" bucket's "Follow-up Status" column.
     */
    public function currentFollowup(): HasOne
    {
        return $this->hasOne(HospitalizationTrackerFollowupModel::class, 'hospitalization_tracker_id', 'id')
            ->latestOfMany();
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(HospitalizationTrackerContactModel::class, 'hospitalization_tracker_id', 'id');
    }

    /**
     * Diagnoses (PD-3498) hang off the tracker's encounter, not the tracker itself — there is no
     * `hospitalization_tracker_id` column on `encounter_diagnoses`. Safe to expose as if it were a
     * direct child here because, in practice, one encounter always has exactly one tracker (confirmed
     * with product), so this never surfaces another tracker's diagnoses.
     */
    public function diagnoses(): HasManyThrough
    {
        return $this->hasManyThrough(
            EncounterDiagnosisModel::class,
            EncounterModel::class,
            'id',
            'encounter_id',
            'encounter_id',
            'id',
        );
    }
}
