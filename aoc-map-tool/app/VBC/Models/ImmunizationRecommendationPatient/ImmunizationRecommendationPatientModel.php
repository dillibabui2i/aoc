<?php

namespace App\VBC\Models\ImmunizationRecommendationPatient;

use App\MssqlPatients;
use App\VBC\Models\ImmunizationRecommendation\ImmunizationRecommendationModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $junction_id
 * @property int $patient_id
 * @property int $recommendation_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read ImmunizationRecommendationModel $recommendation
 */
class ImmunizationRecommendationPatientModel extends Model
{
    use SoftDeletes;

    public $table = 'immunization_recommendations_mssql_patients';
    protected $primaryKey = 'junction_id';
    protected $fillable = [
        'patient_id',
        'recommendation_id',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function recommendation(): BelongsTo
    {
        return $this->belongsTo(ImmunizationRecommendationModel::class, 'recommendation_id', 'id');
    }
}
