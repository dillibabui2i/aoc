<?php

namespace App\VBC\Models\SickPlan;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $domain
 * @property string $activity_code
 * @property string $description
 *
 * @property-read Collection<int, SickPlanItemModel> $sickPlanItems
 */
class SickPlanCatalogModel extends Model
{
    use HasFactory;

    public $table = 'sick_plan_catalog';
    public $timestamps = false;
    protected $fillable = [
        'domain',
        'activity_code',
        'description',
    ];

    public function sickPlanItems(): HasMany
    {
        return $this->hasMany(SickPlanItemModel::class, 'catalog_id', 'id');
    }
}
