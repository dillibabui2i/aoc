<?php

namespace App\VBC\Models\CdcVaccine;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $aoc_name
 * @property string|null $cdc_name
 * @property string|null $cdc_short_description
 * @property int $cvx_group_code
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * @property-read Collection<int, CdcVaccineModel> $vaccines
 */
class CdcVaccineGroupModel extends Model
{
    use SoftDeletes;

    public $table = 'cdc_vaccine_groups';
    protected $fillable = [
        'aoc_name',
        'cdc_name',
        'cdc_short_description',
        'cvx_group_code',
    ];
    protected $casts = [
        'cvx_group_code' => 'integer',
    ];

    public function vaccines(): HasMany
    {
        return $this->hasMany(CdcVaccineModel::class, 'cvx_group_code', 'cvx_group_code');
    }
}
