<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;

class ProcessDanglingRightSignature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rightsignature:dangling';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process Dangling Right signature document';

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
        
        $danglingDocuments = DB::table("aoc_documents")->select("*")->whereNull("document_id")->limit(10)->get();

        foreach($danglingDocuments as $document) {
            echo 'checking ' . htmlspecialchars($document->sending_request_id, ENT_QUOTES, 'UTF-8') . "\n";
            $documentInfoJson = H::getHelper("RightSignature")->checkStatus($document->sending_request_id);
            $documentInfo = json_decode($documentInfoJson,true);
            var_dump($documentInfo);
            echo "\n";
            $templateId = '';
            if(!empty($documentInfo['document_template_id'])) {
                $templateId = $documentInfo['document_template_id'];
                $documentData = H::getHelper("RightSignature")->getDocumentByDocumentId($templateId);
                echo htmlspecialchars($documentData['document']['name'], ENT_QUOTES, 'UTF-8') . ' ' .
                    htmlspecialchars($documentData['document']['sent_at'], ENT_QUOTES, 'UTF-8') . ' ' .
                    htmlspecialchars($documentData['document']['recipients'][0]['name'], ENT_QUOTES, 'UTF-8') . ' ' .
                    htmlspecialchars($documentData['document']['recipients'][0]['status'], ENT_QUOTES, 'UTF-8') . "\n";
                echo "\n";
               // exit;
                $data = ["document_id"=>$templateId,'status'=>$documentData['document']['state'],'send_date'=>date(config('constant')['longdatetimeformat'],strtotime($documentData['document']['sent_at'])),'updated_at'=>date(config('constant')['longdatetimeformat'],strtotime($documentData['document']['sent_at']))];
                var_dump($data);
                echo "\n";

                if($documentData['document']['state']=='executed'){
                    H::getHelper("RightSignature")->downloadSignedDocument($templateId);
                    $data['signed_date'] = date(config('constant')['longdatetimeformat'],strtotime($documentData['document']['executed_at']));
                    $data['updated_at'] = date(config('constant')['longdatetimeformat'],strtotime($documentData['document']['executed_at']));
                }
                H::getHelper("RightSignature")->assignDocumentIdToRequestId($document->sending_request_id,$data);
            }
           
        }
        echo "\nLinking Document Finished";
       return 0;
    }
}
