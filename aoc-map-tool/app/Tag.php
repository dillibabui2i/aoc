<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'created_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function claims()
    {
        return $this->belongsToMany(DB::raw('claims_master'), 'claim_tag', 'tag_id', 'claim_id');
    }
}
