<?php

namespace App\VBC\Models\SickPlan;

use App\MssqlPatients;
use App\VBC\Enum\SickPlan\SickPlanStatusEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $patient_id
 * @property SickPlanStatusEnum $status
 * @property Carbon|null $activated_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read MssqlPatients|null $patient
 * @property-read Collection<int, SickPlanItemModel> $items
 */
class SickPlanModel extends Model
{
    use HasFactory;

    public $table = 'sick_plans';
    protected $fillable = [
        'patient_id',
        'status',
        'activated_at',
        'deactivated_at',
    ];
    protected $casts = [
        'status' => SickPlanStatusEnum::class,
        'activated_at' => 'datetime',
        'deactivated_at' => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(MssqlPatients::class, 'patient_id', 'id')
            ->where('IsLatestAdmit', true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SickPlanItemModel::class, 'sick_plan_id', 'id');
    }
}
