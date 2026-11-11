<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Map extends Model
{
    protected $primaryKey = 'map_id';
    protected $table = 'map_configurations';
    public $timestamps = false;

    protected $casts = [
        'last_uploaded_at' => 'datetime',
    ];

    protected $guarded = [];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id', 'map_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'map_id');
    }

    public function lastUploadedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_uploaded_by_user_id');
    }
}
