<?php

namespace App\VBC\Models\ImmunizationTracker;

use App\MssqlPatients;
use App\User;
use App\VBC\Enum\ImmunizationTracker\ImmunizationTrackerStatusEnum;
use App\VBC\Models\CdcVaccine\CdcVaccineModel;
use App\VBC\Models\VaccineDose\VaccineDoseModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property ImmunizationTrackerStatusEnum $status
 * @property int $patient_id
 * @property int $vaccine_cvx_code
 * @property int|null $assignee_id
 * @property int|null $created_by_id NULL if auto-created based on immunization recommendations
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read CdcVaccineModel|null $vaccine
 * @property-read User|null $assignee
 * @property-read User|null $createdBy
 * @property-read Collection<int, VaccineDoseModel> $doses
 */
class ImmunizationTrackerModel extends Model
{
    use SoftDeletes;

    public $table = 'immunization_trackers';
    protected $fillable = [
        'status',
        'patient_id',
        'vaccine_cvx_code',
        'assignee_id',
        'created_by_id',
    ];
    protected $casts = [
        'status' => ImmunizationTrackerStatusEnum::class,
        'vaccine_cvx_code' => 'integer',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function vaccine(): BelongsTo
    {
        return $this->belongsTo(CdcVaccineModel::class, 'vaccine_cvx_code', 'cvx_code');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id', 'id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id', 'id');
    }

    public function doses(): HasMany
    {
        return $this->hasMany(VaccineDoseModel::class, 'immunization_tracker_id', 'id');
    }
}
