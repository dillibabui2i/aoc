<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserConsent extends Model
{
    use HasFactory;

    protected $table = 'user_consents';
    protected $fillable = [
        'external_user_id',
        'consent_template_id',
        'consent_version',
        'accepted_at',
        'source'
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(
            ConsentTemplate::class,
            'consent_template_id'
        );
    }
}
