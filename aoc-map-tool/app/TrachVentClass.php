<?php

namespace App;

use App\Enums\TrachVentClassRepeatEvery;
use App\Models\ClassCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrachVentClass extends Model
{
    use HasFactory;
    protected $table = 'trach_vent_classes';
    protected $fillable = [
        'type',
        'location_id',
        'instructor_id',
        'class_date',
        'class_time',
        'end_time',
        'capacity',
        'notes',
        'class_type',
        'repeat_every',
        'repeat_on',
        'repeat_until',
    ];

    protected $casts = [
        'repeat_every' => TrachVentClassRepeatEvery::class,
        'repeat_until' => 'date',
    ];

    public function classCategory(): BelongsTo
    {
        return $this->belongsTo(ClassCategory::class, 'type');
    }

    public function branchLocation(): BelongsTo
    {
        return $this->belongsTo(BranchLocations::class, 'location_id');
    }

    public function occurrences(): HasMany
    {
        return $this->hasMany(TrachVentClassOccurrence::class, 'class_id');
    }

    public function isRecurring(): bool
    {
        return $this->repeat_every !== null;
    }

    public function repeatOnDays(): array
    {
        return $this->repeat_on ? explode(',', $this->repeat_on) : [];
    }

    public function isLastOccurrence(string $occurrenceDate): bool
    {
        if (!$this->isRecurring()) {
            return false;
        }

        $lastDate = $this->occurrences()->max('class_date');

        return $lastDate !== null && $lastDate === $occurrenceDate;
    }
}
