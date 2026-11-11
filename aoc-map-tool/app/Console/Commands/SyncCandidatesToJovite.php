<?php

namespace App\Console\Commands;

use App\SyncProcesses\syncCandidateToJobvite;
use Illuminate\Console\Command;


class syncCandidatesToJovite extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:candidatestojobvite';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Candidates';

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
        $syncCandidateToJobvite = new syncCandidateToJobvite();
        $syncCandidateToJobvite->syncCandidate();
       // H::getHelper("syncCandidateToJovite")->syncCandidate();
       /*
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.jobvite.com/api/v2/candidate?api=angelsofcare_job_candidate_api_key&sc=df2263d86621808e6c4a9e1a67899c58&candidateId=eQ6JOiwg',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        
        var_dump(json_decode($response,true));
        exit;
        echo "called"; */


        return 0;
    }
}
