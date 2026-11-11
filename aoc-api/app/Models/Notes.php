<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notes extends Model
{
    public const SOURCE = 'AOC Mobile';
    //
    protected $primaryKey = 'id';
    protected $table = 'notes';
    public $timestamps = true;

    protected $fillable = [
        'entity',
        'entity_id',
        'notes',
        'created_by',
        'completed',
        'completed_at',
        'completed_by',
        'priority',
        'created_at',
        'updated_at',
        'misc1',
        'misc2',
        'misc3',
        'misc4',
        'type',
        'assigned_to',
        'source'
    ];

    protected $attributes = [
        'source' => self::SOURCE,
    ];
}
