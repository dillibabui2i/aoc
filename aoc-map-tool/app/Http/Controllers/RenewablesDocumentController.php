<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use DataTables;
use Illuminate\Http\Request;
use App\Renewables as RenewalbesTable;
use DB;
use Illuminate\Support\Facades\Redirect;


class RenewablesDocumentController extends Controller
{

    public function getDocumentUploadPage(Request $request){
        $requestD = $request->all();

        if(isset($requestD['id']) && !empty($requestD['id'])) {
            $renewables = DB::table("renewables")->select("renewables.*","renewables_types.name")->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.renewable_type_id')->where("renewables.id",base64_decode($requestD['id']))->first();
            if($renewables) {
                return view('renewable.document.upload', ["data" => $renewables]);
            } else {
                return Redirect::to(route('renewabledocument.thankyou'))->withErrors('Renewable not found, please contact Angles of Care');
            }

        } else {
            return Redirect::to(route('renewabledocument.thankyou'))->withErrors('Renewable not found, please contact Angles of Care');
        }
        
        
    }

    public function saveDocumentUpload(Request $request) {
        $requestD = $request->all();
        $request->validate(['renewable_document' =>'required',"renewable_document.*"=>'mimes:jpeg,jpg,png,pdf']);
        
        $files = $request->file('renewable_document');
        $destinationPath = storage_path() . '/documents';
        if(!is_dir(storage_path() . '/documents')){
            mkdir(storage_path() . '/documents');
        }
        if($files) {
            
            $cntr = 1; 
            $renewables = DB::table("renewables")->select("renewables.*","renewables_types.name")->leftJoin('renewables_types','renewables.renewables_type', '=', 'renewables_types.renewable_type_id')->where("renewables.id",$requestD['renewable_id'])->first();
               
            foreach($files as $file) {
                $guessExtension = $file->guessExtension();
                $filename = strtotime("now").'_'.$cntr.'.'.$guessExtension;
                $file->move($destinationPath, $filename);

                $aocDocuments = [];

                $aocDocuments['entity_id'] = $renewables->aoc_provider_id;
                $aocDocuments['entity_type'] = 'provider';
                $aocDocuments['document_type'] = 'renewables';
                $aocDocuments['description'] = $renewables->name;
                $aocDocuments['document_filename'] = $filename;
                $aocDocuments['associate_id'] = $renewables->id;
                $aocDocuments['created_at'] = date("Y-m-d H:i:s");
    
                DB::table("aoc_documents")->insert($aocDocuments);

                $cntr++;
            } 
           
            $renewableUpdate = [];
            $renewableUpdate['outcome'] = 'Clinician uploaded';
            DB::table("renewables")->where("id",$renewables->id)->update($renewableUpdate);

            return Redirect::to(route('renewabledocument.thankyou'))->withSuccess('Documents have been uploaded successfully');

        } else {
            return Redirect::to(route('renewabledocument.thankyou'))->withErrors('There is something wrong with file upload, please contact Angles of Care');
        }

        
    }

    public function getDocumentStatus(Request $request) {

        return view('renewable.document.upload', ["data" => null]);
    }

    


}
