<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read MssqlProviders|null $provider
 * @property-read Candidates|null $candidate
 * @property-read MssqlPatients|null $client
 */
class TrachVentClassRequest extends Model
{
    use HasFactory;
    protected $table = 'trach_vent_class_request';
    protected $fillable = [
        'trach_vent_class_occurrence_id',
        'provider_id',
        'candidate_id',
        'client_id',
        'tracker_id',
        'discipline_snapshot',
        'phone_number_snapshot',
        'start_date',
        'outcome',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    public function provider(): BelongsTo
    {
        return $this->belongsTo(MssqlProviders::class, 'provider_id', 'id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidates::class, 'candidate_id', 'id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'client_id', 'id');
    }

    public function getDisplayNameAttribute(): ?string
    {
        if ($this->provider) {
            return trim($this->provider->FirstName . ' ' . $this->provider->LastName);
        }

        return $this->candidate->fullName ?? null;
    }

    public function occurrence(): BelongsTo
    {
        return $this->belongsTo(TrachVentClassOccurrence::class, 'trach_vent_class_occurrence_id');
    }

    public function getDisplayDisciplineAttribute(): ?string
    {
        return $this->discipline_snapshot;
    }

    public function getDisplayPhoneAttribute(): ?string
    {
        return $this->phone_number_snapshot;
    }

    public function getDisplayStartDateAttribute(): ?string
    {
        return $this->start_date !== null
            ? getFormatedDate($this->start_date)
            : null;
    }

    public function getDisplayClientNameAttribute(): ?string
    {
        return $this->client->PatientName ?? null;
    }
}
