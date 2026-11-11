<?php

namespace App\VBC\Models\Encounter;

use App\MssqlPatients;
use App\VBC\Enum\Encounter\EncounterAdmissionTypeEnum;
use App\VBC\Enum\Encounter\EncounterHospitalizationTypeEnum;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Models\CareCoordination\CareCoordinationModel;
use App\VBC\Models\EncounterDiagnosis\EncounterDiagnosisModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

/**
 * @property int $id
 * @property int $patient_id
 * @property int|null $encounter_parent_id
 * @property EncounterTypeEnum $type
 * @property EncounterHospitalizationTypeEnum|null $hospitalization_type
 * @property EncounterStatusEnum|null $status
 * @property string|null $facility_name
 * @property Carbon|null $admission_at
 * @property EncounterAdmissionTypeEnum|null $admission_type
 * @property string|null $admission_reason
 * @property string|null $transfer_reason
 * @property Carbon|null $expected_discharge_at
 * @property Carbon|null $discharged_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read EncounterModel|null $parent
 * @property-read Collection<int, EncounterModel> $children
 * @property-read Collection<HospitalizationTrackerModel> $hospitalizationTrackers
 * @property-read Collection<int, EncounterDiagnosisModel> $diagnoses
 * @property-read Collection<int, CareCoordinationModel> $careCoordinations
 */
class EncounterModel extends Model
{
    use HasFactory;

    public $table = 'encounters';

    protected $fillable = [
        'patient_id',
        'encounter_parent_id',
        'type',
        'hospitalization_type',
        'status',
        'facility_name',
        'admission_at',
        'admission_type',
        'admission_reason',
        'transfer_reason',
        'expected_discharge_at',
        'discharged_at',
    ];
    protected $casts = [
        'type' => EncounterTypeEnum::class,
        'hospitalization_type' => EncounterHospitalizationTypeEnum::class,
        'status' => EncounterStatusEnum::class,
        'admission_type' => EncounterAdmissionTypeEnum::class,
        'admission_at' => 'datetime',
        'expected_discharge_at' => 'datetime',
        'discharged_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(EncounterModel::class, 'encounter_parent_id', 'id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(EncounterModel::class, 'encounter_parent_id', 'id');
    }

    /**
     * The encounters a transfer chain led through to this one, nearest first (this encounter is not
     * included; the last item is where the patient was first admitted). A transfer opens a child of the
     * encounter it leaves, so walking `encounter_parent_id` up reproduces the chain — a handful of hops.
     *
     * @return SupportCollection<int, EncounterModel>
     */
    public function previousEncounters(): SupportCollection
    {
        $previous = new SupportCollection();
        $encounter = $this->parent;

        while (!empty($encounter)) {
            $previous->push($encounter);
            $encounter = $encounter->parent;
        }

        return $previous;
    }

    public function hospitalizationTrackers(): HasMany
    {
        return $this->hasMany(HospitalizationTrackerModel::class, 'encounter_id', 'id');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(EncounterDiagnosisModel::class, 'encounter_id', 'id');
    }

    public function careCoordinations(): HasMany
    {
        return $this->hasMany(CareCoordinationModel::class, 'encounter_id', 'id');
    }
}
