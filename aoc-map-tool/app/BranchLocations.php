<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BranchLocations extends Model
{
    public const CITY_MC_KINNEY = 'McKinney';
    public const CITY_DALLAS = 'Dallas';

    public $timestamps = true;

    protected $primaryKey = 'id';
    protected $table = 'branch_locations';

    public function cityOf(string $city): bool
    {
        return $this->city === $city;
    }

    public function scopeInTexas($query)
    {
        return $query->where('state', 'Texas');
    }
}
