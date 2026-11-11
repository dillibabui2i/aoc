<?php

namespace App\VBC\Models\SickPlan;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sick_plan_id
 * @property int $catalog_id
 * @property bool $completed
 * @property int|null $completed_by_id
 * @property int|null $author_id
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @property-read SickPlanModel $sickPlan
 * @property-read SickPlanCatalogModel $catalog
 * @property-read User|null $completedBy
 * @property-read User|null $author
 */
class SickPlanItemModel extends Model
{
    use HasFactory;

    public $table = 'sick_plan_items';
    protected $fillable = [
        'sick_plan_id',
        'catalog_id',
        'completed',
        'completed_by_id',
        'author_id',
        'notes',
    ];
    protected $casts = [
        'completed' => 'boolean',
    ];

    public function sickPlan(): BelongsTo
    {
        return $this->belongsTo(SickPlanModel::class, 'sick_plan_id', 'id');
    }

    public function catalog(): BelongsTo
    {
        return $this->belongsTo(SickPlanCatalogModel::class, 'catalog_id', 'id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by_id', 'id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }
}
