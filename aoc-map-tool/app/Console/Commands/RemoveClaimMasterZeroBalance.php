<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
class RemoveClaimMasterZeroBalance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'remove:claimMasterZeroBalance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        H::ExcelLog('--Remove Claim Master Zero Balance START--', 'removeClaimMasterZeroBalance.log');
        $claimsMasterData = DB::table("claims_master")->select('claims_master.*')
        ->where('balance',0)->get()->toArray();
        $claimsMasterData = H::objToArr($claimsMasterData);
        foreach($claimsMasterData as $claimsMasterDetail) {
            try {
                if(!empty($claimsMasterDetail['reason']) || !empty($claimsMasterDetail['subreason']) || !empty($claimsMasterDetail['subreason_details']) || !empty($claimsMasterDetail['notes']) || !empty($claimsMasterDetail['comment']) || !empty($claimsMasterDetail['followedup_at'])) {
                    $logDataInsert = $claimsMasterDetail;
                    $logDataInsert['claims_master_id'] = $claimsMasterDetail['id'];
                    $logDataInsert['archived_at'] = now();
                    unset($logDataInsert['id']);
                    H::ExcelLog('--Inserting into log table for Claim Master ID--'.$logDataInsert['claims_master_id'], 'removeClaimMasterZeroBalance.log');
                    DB::table("claims_master_log")->insert($logDataInsert);
                }
                //delete from main table
                DB::table('claims_master')->where('id', $claimsMasterDetail['id'])->delete();
            } catch (\Throwable $th) {
                H::ExcelLog( "----Throwable----".$th->getMessage(), 'removeClaimMasterZeroBalance.log');
            }
        }
        return Command::SUCCESS;
    }
}
