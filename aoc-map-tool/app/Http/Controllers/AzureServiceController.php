<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\RightSignatureDocuments;
use App\FieldEvaluation;
use App\OfficeEvaluation;

class AzureServiceController extends Controller
{
   
    public function azureServiceForm(Request $request) {

        return view('account.azureform');
    }


   public function azureServiceUploadfile(Request $request) {
        
        $file = $request->file('document');
        $guessExtension = $file->guessExtension();
        $filename = strtotime("now").'.'.$guessExtension;
        $uploadResponse = H::getHelper("AzureBlobService")->uploadBlob("sampletest",$filename,$file->getRealPath());
        if($uploadResponse){
            echo "File Uploaded Successfully";
        } else {
            echo "There is some error uploading file";
        }
   }

   public function createContainer(Request $request) {
        $reqData = $request->all();
    
        if(!empty($reqData['container'])) {
            if(H::getHelper("AzureBlobService")->createContainer($reqData['container'])){
                echo "Container Created Successfully";
            } else {
                echo "There is some error creating Container";
            }
        } else {
            echo "Please Specify 'container' parameter";
        }
   }

   public function openFileFromBlob(Request $request) {
        $reqData = $request->all();

        if(!empty($reqData['blob'])) {
            $blobResponse = H::getHelper("AzureBlobService")->getBlobFromAzure("sampletest",$reqData['blob']);
            if(is_array($blobResponse)){
                header("Content-Type:".$blobResponse[1]);
                // echo $blobResponse[0];
            } else {
                echo "There is something wrong, blob not accessible";
            }
        } else {
            echo "Please Specify 'container' parameter";
        }
    }
}
