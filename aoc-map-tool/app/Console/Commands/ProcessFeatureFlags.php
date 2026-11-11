<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessFeatureFlags extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:featureflags';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Feature Flags After Deploying';

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
        //update the permissions from /config/featureflags.php to permissions table
        $featureflags = config('featureflags');
        $tablename = 'feature_flags';
        foreach ($featureflags as $flag => $settings) {
            echo '============== flag: ' . $flag . "\n";
            var_dump($settings);
            //prepare array to update to database
            $newRow = [];
            $newRow['flag'] = $flag;
            if(isset($settings['created_by'])) {
                $newRow['created_by'] = $settings['created_by'];
            }
            $users = '';
            $userList = '';
            if (isset($settings['users'])) {
                foreach ($settings['users'] as $email) {
                    if ($email != '') {
                        if ($userList != '') {
                            $userList .= ',';
                        }
                        $userList .= $email;
                    }
                }
            }
            $ips = '';
            $ipList = '';
            if (isset($settings['ips'])) {
                foreach ($settings['ips'] as $ip) {
                    if ($ip != '') {
                        if ($ipList != '') {
                            $ipList .= ',';
                        }
                        $ipList .= $ip;
                    }
                }
            }
            $description = $settings['description'];
            $newRow['flag_description'] = $description;
            echo 'users: ' . $userList . "\n";
            echo 'ips: ' . $ipList . "\n";
            echo 'flag_description: ' . $description . "\n";
            //look for match in table for flag
            $data = DB::table($tablename)->select('*')->where('flag', $flag)->get();
            if (count($data) > 0) {
                echo '          Update flag' . "\n";
                $id = $data[0]->id;
                echo 'id: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "\n";
                DB::table($tablename)->whereRaw('`id`= ?', [$id])->update($newRow);
            } else {
                echo '          Insert flag' . "\n";
                $newRow['users'] = $userList;
                $newRow['ips'] = $ipList;
                $newRow['status'] = 1;
                DB::table($tablename)->insert($newRow);
            }
            echo "\n" . "\n";
        }
        return 0;
    }
}
