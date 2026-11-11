<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class FeatureFlags extends Model
{
    use HasFactory;

    protected $fillable = [
        'flag',
        'flag_description',
        'status',
        'users',
        'ips',
        'created_by',
    ];

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Log::channel('featureFlags')->info('Feature Flags saved.');
            Cache::tags(['ff_cache'])->flush();
        });

        static::deleted(function () {
            Cache::tags(['ff_cache'])->flush();
        });
    }
}
