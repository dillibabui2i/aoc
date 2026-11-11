<?php

namespace App\VBC\Models\HospitalizationTracker;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class HospitalizationTrackerTrainingTypeModel extends Model
{
    use HasFactory;

    public $table = 'hospitalization_tracker_training_types';
    protected $fillable = [
        'key',
        'name',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
    ];
}
