<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\Notifications;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades\Crypt;

class ProcessNursysPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nursys:setnewpassword';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'set Nursys New password';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    protected $requestCounter;
    public function __construct()
    {
        parent::__construct();
        $this->requestCounter = 0;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $newPassword = H::getHelper("Nursys")->randomPassword();
        $passwordRequest = ['NewPassword'=>$newPassword];
        H::getHelper("Nursys")->createNewPasswordRequest($passwordRequest);
        return 1;
    }

    


    

}
