<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleMaster extends Model
{
    private const WANTED_NOTES = [
        'Thoughtful AI:',
        'Corresponsing client was not found in NetSmart under this payer'
    ];

    public $table = 'mssql_schedulemaster';

    public function getLatestAINotes(int $lastProcessedId): Collection
    {
        return ScheduleMaster::join('billing_inquery_notes as bin', function ($join) {
            $join->on('mssql_schedulemaster.PatientID', '=', 'bin.PatientID')
                ->whereRaw('bin.start_date = DATE(mssql_schedulemaster.PlannedStart)')
                ->whereRaw('bin.end_date = DATE(mssql_schedulemaster.PlannedEnd)');
        })
            ->where('bin.id', '>', $lastProcessedId)
            ->where(function ($q) {
                $q->where('bin.notes', 'like', '%' . self::WANTED_NOTES[0] . '%')
                    ->orWhere('bin.notes', 'like', '%' . self::WANTED_NOTES[1] . '%');
            })
            ->select(
                'bin.id',
                'bin.PatientID',
                'bin.start_date',
                'bin.end_date',
                'bin.updated_at',
                DB::raw('GROUP_CONCAT(mssql_schedulemaster.id) as sm_ids'),
                'bin.notes',
            )
            ->groupBy('bin.id', 'bin.PatientID', 'bin.start_date', 'bin.end_date')
            ->orderBy('bin.id', 'asc')
            ->get();
    }
}
