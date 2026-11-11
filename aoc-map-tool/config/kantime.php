<?php

$debug = env('KANTIME_DEBUG_SYNC', false);
$cronDebug = env('KANTIME_DEBUG_CRON', '* * * * *');

return [

    'repositories' => [
        App\Kantime\Services\RenewablesSyncService::class => App\Kantime\Repository\BigQuery\RenewablesRepository::class,
        App\Kantime\Services\RenewablesTypeSyncService::class => App\Kantime\Repository\BigQuery\RenewablesTypesRepository::class,
        App\Kantime\Services\ScheduleMasterSyncService::class => App\Kantime\Repository\BigQuery\ScheduleMasterRepository::class,
    ],

    'bigquery' => [
        'project_id' => env('BIGQUERY_PROJECT_ID', 'prod-daas-aoc'),
        'dataset' => env('BIGQUERY_DATASET', 'kt_analytics_hh_aoc'),
        'location' => 'us-central1',
    ],

    'queue' => [
        'daas' => 'daas-sync',
    ],

    'sync' => [
        'overlap_ttl_seconds' => 3600,
        'default_limit' => 1000,
        'debug' => $debug,
    ],

    'sources' => [
        'daas' => [
            'connection' => env('DAAS_CONNECTION', 'daas'),
        ],
    ],

    'secrets' => [
        'bigquery_arn' => env('AWS_SECRETS_ARN', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Kantime scheduling
    |--------------------------------------------------------------------------
    |
    | Keep cron/schedule definitions here so the Kantime module can be moved to
    | another project with minimal changes.
    |
    | These entries are intended to be consumed by Laravel Scheduler (Kernel)
    | via a small bootstrap that loops over `kantime.schedule.jobs`.
    |
    | Fields:
    | - enabled: bool
    | - job: FQCN of the queued Job (preferred) OR command: artisan command string
    | - args: array passed to the job constructor (if your job supports it)
    | - cron: standard 5-part cron expression
    | - queue: target queue name
    | - overlap: whether to prevent overlapping runs (Laravel `withoutOverlapping`)
    | - overlap_ttl: seconds for the overlap lock (optional)
    */
    'schedule' => [
        'defaults' => [
            'queue' => env('KANTIME_QUEUE_DAAS', 'daas-sync'),
            'overlap' => true,
            'overlap_ttl' => (int) env('KANTIME_OVERLAP_TTL', 3600),
            'connection' => 'database',
        ],

        'jobs' => [
            'sync:daas:schedules' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncSchedulemaster::class,
                'cron' => $debug ? $cronDebug : '*/4 * * * *',
            ],

            'sync:daas:schedulescu' => [
                'enabled' => false,
                'job' => App\Kantime\Jobs\SyncSchedulemasterCu::class,
                'cron' => $debug ? $cronDebug : '* 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:schedulemidnightcu' => [
                'enabled' => false,
                'job' => App\Kantime\Jobs\SyncSchedulemasterMidnightCu::class,
                'cron' => $debug ? $cronDebug : '* 0,1,2,3,4,5,6 * * *',
            ],

            'sync:daas:renewables' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncRenewables::class,
                'cron' => $debug ? $cronDebug : '*/30 * * * *',
            ],

            'sync:daas:renewables_types' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncRenewablesTypes::class,
                'cron' => $debug ? $cronDebug : '20 0 * * *',
            ],

            'sync:daas:client_emergency_contact' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientEmergencyContacts::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:client_payment_sources' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientPaymentSource::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:payment_sources' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncPaymentSource::class,
                'cron' => $debug ? $cronDebug : '0 * * * *',
            ],

            'sync:daas:service_codes' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncServiceCodes::class,
                'cron' => $debug ? $cronDebug : '0 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:service_code_rates' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncServiceCodeRates::class,
                'cron' => $debug ? $cronDebug : '0 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:referralsource_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncReferralSource::class,
                'cron' => $debug ? $cronDebug : '0 2 * * *',
            ],

            'sync:daas:supervisory_form_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncSupervisoryForms::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:physician_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncPhysicianMaster::class,
                'cron' => $debug ? $cronDebug : '*/30 * * * *',
            ],

            'sync:daas:claims_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClaimsMaster::class,
                'cron' => $debug ? $cronDebug : '*/10 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:authorizations_service' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncAuthorizationService::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:authorizations' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncAuthorizationsMaster::class,
                'cron' => $debug ? $cronDebug : '*/2 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:billing_inquery_notes' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncBillingInquiryNotes::class,
                'cron' => $debug ? $cronDebug : '*/15 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:claim_denials' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClaimDenials::class,
                'cron' => $debug ? $cronDebug : '*/10 * * * *',
            ],

            'sync:daas:claims_adjustment' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClaimsAdjustment::class,
                'cron' => $debug ? $cronDebug : '*/15 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:claims_detail' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClaimsDetail::class,
                'cron' => $debug ? $cronDebug : '*/15 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:claims_payment' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClaimsPayment::class,
                'cron' => $debug ? $cronDebug : '*/5 1,2,3,4,5,6,18,19,20,21,22,23 * * *',
            ],

            'sync:daas:client_master_additional' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientMasterAdditional::class,
                'cron' => $debug ? $cronDebug : '*/20 * * * *',
            ],

            'sync:daas:client_medication_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientMedicationMaster::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:client_physician_master' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientPhysicianMaster::class,
                'cron' => $debug ? $cronDebug : '0 * * * *',
            ],

            'sync:daas:journal_notes' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncJournalNotes::class,
                'cron' => $debug ? $cronDebug : '*/15 * * * *',
            ],

            'sync:daas:patients' => [
                'enabled' => true,
                'job' => App\Kantime\Jobs\SyncClientMaster::class,
                'cron' => $debug ? $cronDebug : '*/5 * * * *',
            ],
        ],
    ],
];
