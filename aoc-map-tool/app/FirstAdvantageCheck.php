<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class FirstAdvantageCheck extends Model
{
    protected $primaryKey = 'id';
    protected $table = 'first_advantage_checks';
    protected $fillable = [
        'aoc_candidate_id',
        'fa_candidate_id',
        'fa_order',
        'status',
        'application_status',
        'result_status',
        'type'
    ];

    public function hasFACheck(string $candidateId): bool
    {
        return $this->where('fa_candidate_id', $candidateId)->exists();
    }

    public function hasInitiated(int $recruitingScheduleId, string $type): bool
    {
        return $this->where('aoc_candidate_id', $recruitingScheduleId)->where('type', $type)->exists();
    }

    public function updateFields(string $candidateId, array $data): bool
    {
        $row =  $this->where('fa_candidate_id', $candidateId)->orderBy('id', 'desc')->first();

        if (isset($data['fa_order_id'])) {
            $row->fa_order_id = $data['fa_order_id'];
        }

        if (isset($data['status'])) {
            $row->status = $data['status'];
        }

        if (isset($data['application_status'])) {
            $row->application_status = $data['application_status'];
        }

        if (isset($data['result_status'])) {
            $row->result_status = $data['result_status'];
        }

        return $row->save();
    }

    public function updateFieldsByRecruiting(int $recruitingScheduleId, array $data): bool
    {
        return $this->where('aoc_candidate_id', $recruitingScheduleId)->update($data);
    }

    public function findByScheduleIDAndType(int $scheduleId, string $type): ?array
    {
        $check = $this->where('aoc_candidate_id', $scheduleId)->where('type', $type)->orderBy('id', 'desc')->first();

        if ($check) {
            $check = $check->toArray();
            $check['updated_at'] = Carbon::parse($check['updated_at'])->setTimezone(config('app.timezone'))->format(
                'm/d/y g:i A'
            );
        }

        return $check;
    }

    public function getApplicationStatusAttribute(): string
    {
        return match ($this->attributes['application_status']) {
            'Not Started' => 'Pending Start',
            'Started' => 'In Progress',
            'DealBroken' => 'Cancelled',
            'Completed' => 'Completed',
            'Expired' => 'Expired',
            'Deleted' => 'Deleted',
            default => 'Pending Start'
        };
    }

    public function getStatusAttribute(): string
    {
        return match ($this->attributes['status']) {
            'InProgress' => 'In Progress',
            'Completed' => 'Completed',
            default => '--'
        };
    }

    public function getResultStatusAttribute(): string
    {
        return match ($this->attributes['result_status']) {
            'Pass' => 'Pass',
            'Review' => 'Review',
            'Fail' => 'Fail',
            default => '--'
        };
    }
}
