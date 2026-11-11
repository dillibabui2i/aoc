<?php

namespace App\Console\Commands;

use App\SyncProcesses\MssqlCheckEtl;
use App\SyncProcesses\MssqlClientMap;
use App\SyncProcesses\MssqlColdCallMap;
use App\SyncProcesses\MssqlCoverageClinicianMap;
use App\SyncProcesses\MssqlIntake;
use App\SyncProcesses\MssqlNthMap;
use App\SyncProcesses\MssqlNthSchedules;
use App\SyncProcesses\MssqlPatients;
use App\SyncProcesses\MssqlProviders;
use App\SyncProcesses\MssqlRecruiterTracker;
use App\SyncProcesses\MssqlReference;
use App\SyncProcesses\MssqlTextBlastMap;
use App\SyncProcesses\MssqlScheduleMaster;
use App\SyncProcesses\MssqlNeedToHireChecklist;
use App\SyncProcesses\MssqlRenewableItems;
use App\SyncProcesses\MssqlReferringProviders;
use Illuminate\Console\Command;

class SyncMssql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:mssql {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Sync MSSQL Data
    Available Types:
    checketl - check to see if today's ETL job is completed
    clientmap - process client map data
    coldcallmap - process cold call map data
    coverageclinicianmap - process coverage clinician map data
    textblastmap - process Text Blast map data
    reference - process Reference table data
    patients - process patient table data
    intake - process intake patient table data
    providers - process provider table data
    tracker - process recruiterTracker data to mssql
    tmptracker - process mssql recruitertracer to a temp table
    fixnthmap - fix nth map schedules
    renewables - process RenewableItems table
    renewabletypes - process renewable types
    servicecodes - process distinct service codes
    referring_providers - process ReferringProviders table" ;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $type = $this->argument('type');

        switch ($type) {
            case 'checketl':
                echo "checking to see if today's ETL job is completed";
                $process = new MssqlCheckEtl();
                $process->checkETL();
                break;
            case 'clientmap':
                echo 'processing client map data';
                $process = new MssqlClientMap();
                $process->syncClientMap();
                break;
            case 'coldcallmap':
                echo 'processing cold calling map data';
                $process = new MssqlColdCallMap();
                $process->syncColdCall();
                break;
            case 'coverageclinicianmap':
                echo 'processing coverage clinician map data';
                $process = new MssqlCoverageClinicianMap();
                $process->syncCoverageClinicianMap();
                break;
            case 'textblastmap':
                echo 'processing Text Blast map data';
                $process = new MssqlTextBlastMap();
                $process->syncTextBlast();
                break;
            case 'reference':
                echo 'processing Reference table data';
                //EmploymentStatus, Facilities
                $process = new MssqlReference();
                $process->syncReference();
                break;
            case 'query':
                echo 'test mssql with queries';
                $process = new MssqlReference();
                $process->queryMssql();
                break;
            case 'patients':
                echo 'processing patient table data';
                $process = new MssqlPatients();
                $process->syncPatients();
                break;
            case 'intake':
                    echo 'processing intake table data';
                    $process = app(MssqlIntake::class);
                    $process->syncIntake();
                    break;
            case 'providers':
                echo 'processing provider table data';
                $process = new MssqlProviders();
                $process->syncProviders();
                break;
            case 'paycom':
                echo 'processing paycom table data';
                $process = new MssqlProviders();
                $process->syncPayCom();
                break;
            case 'matchtrackers':
                echo 'processing provider table data';
                $process = new MssqlProviders();
                $process->matchToTrackers();
                break;
            case 'tracker':
                echo 'processing recruiterTracker data';
                $process = new MssqlRecruiterTracker();
                $process->syncTrackerToMssql();
                break;
            case 'tmptracker':
                    echo 'processing mssql recruiterTracker data to a temp table';
                    $process = new MssqlRecruiterTracker();
                    $process->tmpSyncRecruiterTracker();
                    break;
            case 'schedulemaster':
                echo 'processing schedule master data';
                $process = new MssqlScheduleMaster();
                $process->syncScheduleMaster('month');
                break;
            case 'smyesterday':
                echo 'processing schedule master data from yesterday';
                $process = new MssqlScheduleMaster();
                $process->syncScheduleMaster('yesterday');
                break;
            case 'smlastweek':
                echo 'processing schedule master data from last week';
                $process = new MssqlScheduleMaster();
                $process->syncScheduleMaster('lastweek');
                break;
            case 'fixnthmap':
                echo 'processing Fix NTH Map';
                $process = new MssqlNeedToHireChecklist();
                $process->fixNthMapSchedules();
                break;
            case 'renewables':
                echo 'processing RenewableItems table';
                $process = new MssqlRenewableItems();
                $process->syncRenewableItems();
                break;
            case 'renewabletypes':
                echo 'processing Renewable types table';
                $process = new MssqlRenewableItems();
                $process->syncRenewableTypes();
                break;
            case 'servicecodes':
                echo 'processing distinct service codes';
                $process = new MssqlScheduleMaster();
                $process->syncServiceCodes();
                break;
            case 'referring_providers':
                echo 'processing referring providers table';
                $process = new MssqlReferringProviders();
                $process->syncReferringProviders();
                break;
			case 'special_payer_group':
                echo 'processing special payer group table data';
                $process = new MssqlPatients();
                $process->syncSpecialPayerGroup();
                break;
            default:
                echo 'no match found!';
        }
        return 0;
    }
}
