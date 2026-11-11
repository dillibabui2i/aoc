<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\PayerPriorityConfig
 *
 * @property int $id
 * @property string $state
 * @property string $state_abbr
 * @property int $rank
 * @property string $payer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PayerPriorityConfig extends Model
{
    protected $table = 'payer_priority_config';

    protected $fillable = [
        'state',
        'state_abbr',
        'rank',
        'payer',
    ];
}
