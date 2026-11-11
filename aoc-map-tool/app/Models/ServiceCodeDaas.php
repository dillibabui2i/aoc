<?php

namespace App\Models;

use App\Enums\Intake\AdmissionServiceNameEnum;
use Illuminate\Database\Eloquent\Model;

class ServiceCodeDaas extends Model
{
    protected $table = 'service_codes_daas';

    protected $guarded = [];

    protected $casts = [
        'is_admissions' => 'boolean',
        'is_respite' => 'boolean',
    ];

    /**
     * Resets and sets is_admissions in a single statement, so readers never see all codes as 0.
     * Raw statement on purpose: Eloquent mass update would also touch updated_at of every row.
     *
     * @return int affected rows
     */
    public function refreshAdmissionsFlag(): int
    {
        return $this->query()->whereIn('name', AdmissionServiceNameEnum::values())->update(['is_admissions' => 1]);
    }

    /**
     * Only raises the flag (never resets it to 0), same as before the refactoring.
     *
     * @return int affected rows
     */
    public function refreshRespiteFlag(): int
    {
        // toBase(): plain query builder update, without touching updated_at
        return $this->query()
            ->where('name', 'like', '%respite%')
            ->update(['is_respite' => 1]);
    }
}
