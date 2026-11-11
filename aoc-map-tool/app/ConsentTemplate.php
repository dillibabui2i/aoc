<?php

namespace App;

use App\Enums\ConsentTemplates\StatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsentTemplate extends Model
{
    protected $fillable = [
        'type',
        'version',
        'status',
        'language',
        'content',
        'created_by',
        'activated_at',
    ];

    public function supersedeOtherPublished(): int
    {
        return $this->newQuery()
            ->where('type', $this->type)
            ->where('language', $this->language)
            ->where('id', '<>', $this->id)
            ->where('status', StatusEnum::PUBLISHED->value)
            ->update(['status' => StatusEnum::SUPERSEDED->value]);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
