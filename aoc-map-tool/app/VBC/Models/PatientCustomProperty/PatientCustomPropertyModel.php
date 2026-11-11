<?php

namespace App\VBC\Models\PatientCustomProperty;

use App\MssqlPatients;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property int $definition_id
 * @property mixed $value
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read PatientCustomPropertyDefinitionModel $definition
 */
class PatientCustomPropertyModel extends Model
{
    use HasFactory;

    public $table = 'patient_custom_properties';
    protected $fillable = [
        'patient_id',
        'definition_id',
        'value',
    ];
    protected $casts = [
        'value' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(PatientCustomPropertyDefinitionModel::class, 'definition_id', 'id');
    }
}
