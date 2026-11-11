<?php

namespace App\VBC\Models\CareCoordination;

use App\User;
use App\VBC\Models\Encounter\EncounterModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $encounter_id
 * @property int $care_coordination_type_id
 * @property int|null $assigned_to_id
 * @property Carbon|null $due_at
 * @property Carbon|null $completed_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read EncounterModel $encounter
 * @property-read CareCoordinationTypeModel|null $type
 * @property-read User|null $assignedTo
 */
class CareCoordinationModel extends Model
{
    use HasFactory;

    public $table = 'care_coordinations';
    protected $fillable = [
        'encounter_id',
        'care_coordination_type_id',
        'assigned_to_id',
        'due_at',
        'completed_at',
        'notes',
    ];
    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(EncounterModel::class, 'encounter_id', 'id');
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(CareCoordinationTypeModel::class, 'care_coordination_type_id', 'id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_id', 'id');
    }
}
