<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\IPEAAttachment;
use Illuminate\Http\Request;

class ManageIPEAAttachementController extends Controller
{
    public function index()
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('ipea_email_attachments');

        return view('ipea.attachmentgrid', ['dataTable'=>$dataTable]);
    }

    public function ajaxipeaattachment(Request $request)
    {
        return $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'ipea_email_attachments');
    }

    public function edit(Request $request)
    {
        try {
            $data = IPEAAttachment::where('id', $request->input('id'))->first();

            return view('ipea.attachmentform', ['data'=>$data]);
        } catch (\Exception $e) {
            return redirect(route('loginrestrictions'));
        }
    }

    public function save(Request $request)
    {
        //try {
        $requestData = $request->all();

        $file = $request->file('file_path');

        if (!empty($file)) {
            $destinationPath = storage_path() . '/ipeaattachments';
            $file->move($destinationPath, $file->getClientOriginalName());
        }

        if (isset($requestData['id']) && !empty($requestData['id'])) {
            $ipeaattachment = IPEAAttachment::find($requestData['id']);
        } else {
            $ipeaattachment = new IPEAAttachment;
        }

        if ($file) {
            $destinationPath = storage_path() . '/ipeaattachments/';
            $filePath = realpath($destinationPath . $ipeaattachment->file_path);
            if ($filePath !== false && strpos($filePath, $destinationPath) === 0 && file_exists($filePath)) {
                @unlink($filePath);
            }
            $ipeaattachment->file_path = $file->getClientOriginalName();
        }
        $ipeaattachment->facilities = isset($requestData['facilities']) ? implode(',', $requestData['facilities']) : '';

        $ipeaattachment->skills = isset($requestData['skills']) ? implode(',', $requestData['skills']) : '';

        $ipeaattachment->save();

        return redirect(route('manageipeaattachment.grid'));
        //} catch(\Exception $e) {
        //	return redirect(route("manageipeaattachment.grid"));
        //}
    }

    public function destroy(Request $request)
    {
        try {
            $ipeaattachment = IPEAAttachment::find($request['id']);
            $destinationPath = storage_path() . '/ipeaattachments/';
            $filePath = realpath($destinationPath . $ipeaattachment->file_path);
            if ($filePath !== false && file_exists($filePath)) {
                @unlink($filePath);
            }
            IPEAAttachment::where('id', $request->input('id'))->delete();
        } catch (\Exception $e) {
            H::ExcelLog("Error : " . $e->getMessage(),'destroy.log');
            exit;
        }

        return redirect(route('manageipeaattachment.grid'));
    }

    public function download(Request $request)
    {
        $ipeaattachment = IPEAAttachment::find($request['id']);
        $destinationPath = storage_path() . '/ipeaattachments/';

        if (!empty($ipeaattachment->file_path) && file_exists($destinationPath . $ipeaattachment->file_path)) {
            $type = explode('.', $ipeaattachment->file_path);
            $ext = $type[count($type) - 1];
            $headers = ['Content-Type: application/' . $ext];

            return response()->download($destinationPath . $ipeaattachment->file_path, $ipeaattachment->file_path, $headers);
        } else {
            return redirect(route('manageipeaattachment.grid'));
        }
    }
}
