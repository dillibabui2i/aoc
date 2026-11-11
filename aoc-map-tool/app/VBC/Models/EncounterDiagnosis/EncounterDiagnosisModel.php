<?php

namespace App\VBC\Models\EncounterDiagnosis;

use App\User;
use App\VBC\Enum\Diagnosis\DiagnosisClinicalStatusEnum;
use App\VBC\Enum\Diagnosis\DiagnosisRankEnum;
use App\VBC\Enum\Diagnosis\DiagnosisSystemEnum;
use App\VBC\Enum\Diagnosis\DiagnosisVerificationStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $encounter_id
 * @property int|null $created_by_id
 * @property string $icd_10_code
 * @property string|null $description
 * @property DiagnosisSystemEnum $system
 * @property DiagnosisRankEnum $rank
 * @property DiagnosisClinicalStatusEnum $clinical_status
 * @property DiagnosisVerificationStatusEnum $verification_status
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read EncounterModel $encounter
 * @property-read User|null $createdBy
 */
class EncounterDiagnosisModel extends Model
{
    use HasFactory;

    public $table = 'encounter_diagnoses';
    protected $fillable = [
        'encounter_id',
        'created_by_id',
        'icd_10_code',
        'description',
        'system',
        'rank',
        'clinical_status',
        'verification_status',
        'notes',
    ];
    protected $casts = [
        'system' => DiagnosisSystemEnum::class,
        'rank' => DiagnosisRankEnum::class,
        'clinical_status' => DiagnosisClinicalStatusEnum::class,
        'verification_status' => DiagnosisVerificationStatusEnum::class,
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(EncounterModel::class, 'encounter_id', 'id');
    }

    /** Who logged the diagnosis — always the current user at creation time, never edited afterward. */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }
}
