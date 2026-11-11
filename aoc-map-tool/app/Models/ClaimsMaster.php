<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimsMaster extends Model
{

    protected $primaryKey = 'id';

    protected $table = 'claims_master';

    protected $guarded = [];

    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(Adjustment::class, 'adjustment_category_id', 'id');
    }

}
