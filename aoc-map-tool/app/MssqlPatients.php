<?php

namespace App;

use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyModel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read MssqlFacilities|null $facility
 * @property-read Collection<PatientCustomPropertyModel> $customProperties
 */
class MssqlPatients extends Model
{
    public function facility(): BelongsTo
    {
        return $this->belongsTo(MssqlFacilities::class, 'aoc_facility_id', 'id');
    }

    public function customProperties(): HasMany
    {
        return $this->hasMany(PatientCustomPropertyModel::class, 'patient_id', 'id');
    }
}
