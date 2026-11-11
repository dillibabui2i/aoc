<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentTemplateLog extends Model
{
    protected $fillable = [
        'updated_date',
        'created_by',
        'document_type',
        'action_type',
        'document_version',
        'document_language',
        'updated_content',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
