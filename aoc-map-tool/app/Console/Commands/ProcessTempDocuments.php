<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ProcessTempDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'process:documentcleanup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Temp Document Cleanup';

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
       $directoryName= "viewdocuments";
       echo $directoryPath = base_path().'/'.$directoryName;
       if(is_dir($directoryPath)) {
            if ($dh = opendir($directoryPath)){
                while (($file = readdir($dh)) !== false){
                    if($file!='.' && $file!='..') {
                        
                        //echo "\nfilename:" . $file . "-".date("F d Y H:i:s.", filectime($directoryPath.'/'.$file))."\n";
                       
                        $diff = time()-filectime($directoryPath.'/'.$file);
                        $minute = $diff/60;
                        echo "\nTime:".$minute;
                        
                        if($minute > 15) {
                            unlink($directoryPath.'/'.$file);
                            echo "\nRemoved !!!";
                        }
                        
                    } 
                    
                }
                closedir($dh);
            }
       } else {
        echo "no directory  exist";
       }

        return 0;
    }
}
