<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class GenerateEncryptedText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cipher:generate {text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Cipher';

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
        $valueToEncrypt = $this->argument('text');
        echo 'Encrypting: ' . $valueToEncrypt . "\n";
        if($valueToEncrypt!=''){
            $encryptedValue = Crypt::encryptString($valueToEncrypt);
            echo $encryptedValue . "\n";
            $decrypted = Crypt::decryptString($encryptedValue);
            echo $decrypted . "\n";
        }
        return 0;
    }
}
