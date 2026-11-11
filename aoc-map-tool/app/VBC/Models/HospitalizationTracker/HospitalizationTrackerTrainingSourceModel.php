<?php

namespace App\VBC\Models\HospitalizationTracker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HospitalizationTrackerTrainingSourceModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_training_sources';
    protected $fillable = [
        'name',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
