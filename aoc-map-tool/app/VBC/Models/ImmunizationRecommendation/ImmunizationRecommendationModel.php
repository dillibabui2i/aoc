<?php

namespace App\VBC\Models\ImmunizationRecommendation;

use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationFrequencyEnum;
use App\VBC\Enum\ImmunizationRecommendation\ImmunizationRecommendationStatusEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\ImmunizationDoseAge\ImmunizationDoseAgeModel;
use App\VBC\Models\ImmunizationRecommendationPatient\ImmunizationRecommendationPatientModel;
use App\VBC\Models\ImmunizationRecommendedFor\ImmunizationRecommendedForModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vaccine_cvx_code
 * @property ImmunizationRecommendationFrequencyEnum $frequency
 * @property string|null $originating_state 2-char state abbreviation, NULL if federal
 * @property ImmunizationRecommendationStatusEnum $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read CdcVaccineModel|null $vaccine
 * @property-read Collection<int, ImmunizationDoseAgeModel> $doseAges
 * @property-read Collection<int, ImmunizationRecommendedForModel> $recommendedFor
 * @property-read Collection<int, ImmunizationRecommendationPatientModel> $patientLinks
 */
class ImmunizationRecommendationModel extends Model
{
    use SoftDeletes;

    public $table = 'immunization_recommendations';
    protected $fillable = [
        'vaccine_cvx_code',
        'frequency',
        'originating_state',
        'status',
    ];
    protected $casts = [
        'vaccine_cvx_code' => 'integer',
        'frequency' => ImmunizationRecommendationFrequencyEnum::class,
        'status' => ImmunizationRecommendationStatusEnum::class,
    ];

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(CdcVaccineModel::class, 'vaccine_cvx_code', 'cvx_code');
    }

    public function doseAges(): HasMany
    {
        return $this->hasMany(ImmunizationDoseAgeModel::class, 'recommendation_id', 'id');
    }

    public function recommendedFor(): HasMany
    {
        return $this->hasMany(ImmunizationRecommendedForModel::class, 'recommendation_id', 'id');
    }

    public function patientLinks(): HasMany
    {
        return $this->hasMany(ImmunizationRecommendationPatientModel::class, 'recommendation_id', 'id');
    }
}
