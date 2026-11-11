<?php

namespace App;

use App\Models\CandidateScreening;
use App\Models\Offer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Candidates extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'candidates';
    protected $guarded = [];

    public function offer(): hasOne
    {
        return $this->hasOne(Offer::class, 'employee_id', 'id');
    }

    public function screening(): hasOne
    {
        return $this->hasOne(CandidateScreening::class, 'candidate_id', 'id');
    }

    public function interviewManagerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'interviewManager');
    }
}
