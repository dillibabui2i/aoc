<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class SupervisoryVisitsRepository
{
    public function updateSupVisitNextDateFromDaas($arg, $mode): void
    {
        Log::channel('daasSupVisit')->info('Mode: ' . $mode);
        $arg = array_change_key_case($arg);

        Log::channel('daasSupVisit')->info('---Update SupVisit NextDate From Daas START---');
        Log::channel('daasSupVisit')->info('BillingInternalPatientID=' . $arg['client_id']);
        Log::channel('daasSupVisit')->info('BillingProviderID=' . $arg['supervisee_cgid']);

        try {
            $bucket = DB::table('supervisory_visits')
                ->select('supervisory_visits.*')
                ->leftJoin('mssql_providers', function ($query) {
                    $query->on('mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id');
                })
                ->leftJoin('mssql_patients', function ($query) {
                    $query->on('mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id');
                })->where('mssql_patients.BillingInternalPatientID', $arg['client_id'])
                ->where('mssql_providers.BillingProviderID', $arg['supervisee_cgid'])
                ->whereNull('supervisory_visits.completed_at')
                ->active('supervisory_visits')
                ->orderByRaw('COALESCE(supervisory_visits.form_sup_visit_date, supervisory_visits.last_visit) DESC')
                ->orderBy('supervisory_visits.id', 'desc')
                ->first();

            if (!empty($bucket)) {
                Log::channel('daasSupVisit')->info('Form Date=' . $arg['form_date']);
                Log::channel('daasSupVisit')->info('Days=' . $bucket->cycle);

                $visit_date_due = date('Y-m-d', strtotime($arg['form_date'] . '+ ' . $bucket->cycle . ' days'));

                Log::channel('daasSupVisit')->info(' Old visit date due=' . $bucket->visit_date_due);
                Log::channel('daasSupVisit')->info(' New visit date due=' . $visit_date_due);

                DB::table('supervisory_visits')->where('id', $bucket->id)
                    ->active()
                    ->update([
                        'last_visit' => $arg['form_date'],
                        'visit_date_due' => $visit_date_due,
                    ]);
            } else {
                Log::channel('daasSupVisit')
                    ->warning('No Record for=' . $arg['client_id'] . ' AND BillingProviderID=' . $arg['supervisee_cgid']);
            }

            //Check completed records for form date match with KT date
            $completedBucket = DB::table('supervisory_visits')
                ->select('supervisory_visits.*')
                ->leftJoin('mssql_providers', function ($query) {
                    $query->on('mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id');
                })
                ->leftJoin('mssql_patients', function ($query) {
                    $query->on('mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id');
                })->where('mssql_patients.BillingInternalPatientID', $arg['client_id'])
                ->where('mssql_providers.BillingProviderID', $arg['supervisee_cgid'])
                ->whereNotNull('supervisory_visits.completed_at')
                ->active('supervisory_visits')
                ->orderBy('supervisory_visits.id', 'desc')->first();

            if (!empty($completedBucket)) {
                Log::channel('daasSupVisit')
                    ->info('Completed KT Form Master Date: ' . $arg['form_date']);
                Log::channel('daasSupVisit')
                    ->info('Completed form_sup_visit_date: ' . $completedBucket->form_sup_visit_date);

                if (strtotime($arg['form_date']) == strtotime($completedBucket->form_sup_visit_date)) {
                    DB::table('supervisory_visits')
                        ->where('id', $bucket->id)
                        ->active()
                        ->update(['visit_not_matched' => 0]);
                } else {
                    Log::channel('daasSupVisit')
                        ->info('KanTime form_date not matched with sup visit form date for: ' . $arg['client_id'] .
                            ' AND BillingProviderID=' . $arg['supervisee_cgid']);
                }
            }
        } catch (\Throwable $th) {
            Log::channel('daasSupVisit')->error('----Throwable----' . $th->getMessage());
        }

        Log::channel('daasSupVisit')->info('---Update SupVisit NextDate From Daas END---');
    }
}
