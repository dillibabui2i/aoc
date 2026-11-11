<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\BranchLocations;
use Illuminate\Http\Request;

class IPEAEmailTemplate extends Controller
{
    public function index()
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('branch_locations');

        return view('ipea.emailtemplategrid', ['dataTable'=>$dataTable]);
    }

    public function ajaxipeatemplate(Request $request)
    {
        return $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'branch_locations', 0);
    }

    public function edit(Request $request)
    {
        try {
            $data = null;
            if ($request->input('id')) {
                $data = BranchLocations::where('id', $request->input('id'))->first();
            }
            if (empty($data['email_body'])) {
                $data['email_body'] = view('emailtemplate.ipea');
            }

            return view('ipea.emailtemplateform', ['data'=>$data]);
        } catch (\Exception $e) {
            return redirect(route('ipea.emailtemplategrid'));
        }
    }

    public function uploadfiles(Request $request)
    {
        $imageFolder = 'images/';
        $file = $request->file('file');
        $timestamp = time();
        if (!empty($file)) {
            $destinationPath = storage_path() . '/emailtemplate';
            $file->move($destinationPath, $timestamp . '_' . $file->getClientOriginalName());
            echo json_encode(['location' =>  env('APP_URL') . '/storage/emailtemplate/' . $timestamp . '_' . $file->getClientOriginalName()]);
            exit;
        }

        return '';
    }

    public function save(Request $request)
    {
        //try {
        $requestData = $request->all();
        $ipeaTemplate = '';

        $id = isset($requestData['id']) ? $requestData['id'] : "";

        if(!empty($id)) {
            $ipeaTemplate = BranchLocations::find($id);
        } else {
            $ipeaTemplate = new BranchLocations;
        }
        $ipeaTemplate->branch = isset($requestData['branch']) ? $requestData['branch'] : '';
        $ipeaTemplate->address = isset($requestData['address']) ? $requestData['address'] : '';
        $ipeaTemplate->city = isset($requestData['city']) ? $requestData['city'] : '';
        $ipeaTemplate->state = isset($requestData['state']) ? $requestData['state'] : '';
        $ipeaTemplate->zipcode = isset($requestData['zipcode']) ? $requestData['zipcode'] : '';
        $ipeaTemplate->phone_number = isset($requestData['phone_number']) ? toE164($requestData['phone_number']) : null;
        $ipeaTemplate->IPEA_instructions = isset($requestData['IPEA_instructions']) ? $requestData['IPEA_instructions'] : '';
        $ipeaTemplate->email_subject = isset($requestData['email_subject']) ? $requestData['email_subject'] : '';
        $ipeaTemplate->email_body = isset($requestData['email_body']) ? $requestData['email_body'] : '';
        $ipeaTemplate->branch_email = isset($requestData['branch_email']) ? $requestData['branch_email'] : '';

        $ipeaTemplate->save();

        return redirect(route('emailtemplate.grid'))->with('success', 'Branch saved.');
    }

    public function destroy(Request $request)
    {
        try {
            $ipeaTemplate = BranchLocations::find($request['id']);
            $ipeaTemplate->delete();
        } catch (\Exception $e) {
            H::ExcelLog('ERROR: ' . $e->getMessage(),'ipeaEmailDestroy.log');
            exit;
        }

        return redirect(route('emailtemplate.grid'));
    }
}
