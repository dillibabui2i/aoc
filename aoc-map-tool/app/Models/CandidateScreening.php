<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CandidateScreening extends Model
{
    use HasFactory;

    public $guarded = ['id'];

    public function isUnqualified(): bool
    {
        return !$this->qualified;
    }

    public function recruiter(): hasOne
    {
        return $this->hasOne(User::class, 'id', 'recruiter_id');
    }
}
