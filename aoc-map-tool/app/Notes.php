<?php

namespace App;

use App\Enums\TodoSourceEnum;
use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'notes';
    public $timestamps = true;

    protected $fillable = [
        'entity',
        'entity_id',
        'notes',
        'type',
        'created_by',
        'completed',
        'completed_at',
        'completed_by',
        'priority',
        'source',
        'created_at',
        'updated_at',
    ];

    protected $attributes = [
        'source' => TodoSourceEnum::AOC_PLATFORM->value
    ];
}
