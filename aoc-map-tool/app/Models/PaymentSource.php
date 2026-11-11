<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSource extends Model
{

    protected $primaryKey = 'id';

    protected $table = 'payment_source';

    protected $guarded = [];

    public function grossMarginConfigurations()
    {
        return $this->hasMany(GrossMarginConfiguration::class, 'payer_id');
    }
}
