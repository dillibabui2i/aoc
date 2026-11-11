<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Permissions After Deploying';

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
        //update the permissions from /config/permissions.php to permissions table
        $permissions = config('permissions');
        $tablename = 'permissions';
        foreach ($permissions as $group => $perms) {
            echo '============== group: ' . $group . "\n";
            foreach ($perms as $perm => $settings) {
                echo '     ----- permission: ' . $perm . "\n";
                var_dump($settings);
                //prepare array to update to database
                $newRow = [];
                $newRow['group'] = $group;
                $newRow['name'] = $perm;
                $newRow['route_name'] = $settings['route'];
                $route = $settings['route'];
                $related_routes = '';
                if (isset($settings['related'])) {
                    foreach ($settings['related'] as $related) {
                        if ($related != '') {
                            if ($related_routes != '') {
                                $related_routes .= ',';
                            }
                            $related_routes .= $related;
                        }
                    }
                }
                echo 'related routes:' . $related_routes . "\n";
                $newRow['related_routes'] = $related_routes;
                //look for match in table for group and name
                //$data = DB::table($tablename)->select('*')->where('group',$group)->where('name',$perm)->get();
                $data = DB::table($tablename)->select('*')->where('route_name', $route)->get();
                if (count($data) > 0) {
                    echo '          Update permission' . "\n";
                    $id = $data[0]->id;
                    echo 'id: ' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . "\n";
                    DB::table($tablename)->whereRaw('`id`= ?', [$id])->update($newRow);
                } else {
                    echo '          Insert permission' . "\n";
                    DB::table($tablename)->insert($newRow);
                }
                echo "\n" . "\n";
            }
            echo "\n" . "\n";
        }

        return 0;
    }
}
