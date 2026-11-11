<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrachVentClassOccurrence extends Model
{
    use HasFactory;
    protected $table = 'trach_vent_class_occurrences';
    protected $fillable = [
        'class_id',
        'class_date',
        'instructor_id',
        'capacity',
        'notes',
        'class_time',
        'end_time',
        'class_type',
    ];

    public function trachVentClass(): BelongsTo
    {
        return $this->belongsTo(TrachVentClass::class, 'class_id');
    }

    private const INHERITED_FROM_CLASS = ['instructor_id', 'capacity', 'notes', 'class_time', 'end_time', 'class_type'];

    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if ($value === null && in_array($key, self::INHERITED_FROM_CLASS, true)) {
            return $this->trachVentClass->{$key};
        }

        return $value;
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(TrachVentClassRequest::class, 'trach_vent_class_occurrence_id')
            ->active()
            ->where(function ($query) {
                $query->whereHas('candidate', fn ($query) => $query->active('candidates'))
                    ->orWhereHas('provider', fn ($query) => $query->active('mssql_providers'));
            });
    }

    public function enrolledCount(): int
    {
        return $this->enrollments_count ?? $this->enrollments()->count();
    }
}
