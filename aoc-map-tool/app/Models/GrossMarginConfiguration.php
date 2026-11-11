<?php

namespace App\Models;

use App\Enums\ManagePayer\GrossMarginLobEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GrossMarginConfiguration extends Model
{
    protected $fillable = [
        'payer_id',
        'lob_id',
        'lob_enum',
        'branch_ids',
        'recruiter_coverage_target',
        'recruiter_escalated',
        'tl_manager_escalated',
        'tl_manager_whole_home_escalated',
        'exec_team_escalated',
        'coverage_escalated',
        'coverage_tl_manager_escalated',
        'coverage_tl_manager_whole_home_escalated',
        'coverage_exec_team_escalated',
    ];

    protected $casts = [
        'branch_ids' => 'array',
        'lob_enum' => GrossMarginLobEnum::class,
    ];

    /**
     * @return HasOne
     */
    public function lob(): HasOne
    {
        return $this->hasOne(LOB::class, 'LOBID', 'lob_id');
    }
}
