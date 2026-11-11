<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mail;
use Validator;
class RequestFormController extends Controller
{
    //
    public function getEnhancementSuggestionForm(Request $request)
    {
        $myTitle = 'Suggest an Enhancement';
        return view('common.enhancement_suggestion', compact('myTitle'));
    }

    public function postEnhancementSuggestion(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'screenshot.*' => 'mimes:jpeg,png,jpg,pdf|max:2048',
            ], [
                'screenshot.*.max' => 'Max allowed size is up to 2MB',
                'screenshot.*.mimes' => 'Uploaded file must be a file of type: jpeg, png, jpg, pdf.'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => 0, 'error' => $validator->errors()->all()]);
            }
            $data = [];
            $data['from_name']  =  auth()->user()->name;
            $data['from_email']  =  env('MAIL_FROM_ADDRESS');
            $data['reply_to'] = auth()->user()->email;
            $data['body']  = $request->explanation_detail;
            $data['subject']  = 'Enhancement suggestion - ' . $request->subject;
            $data['to_email']  = 'appsupport@angelsofcare.com';
            $data['file'] = $request->screenshot;
            Mail::send([], [], function ($message) use ($data, $request) {
                $message->from($data['from_email'], $data['from_name']);
                $message->subject($data['subject']);
                $message->replyTo($data['reply_to'], $data['from_name']);
                $message->to($data['to_email']);
                $message->html($data['body']);
                if (!empty($request->screenshot)) {
                    foreach ($request->screenshot as $file) {
                    $uniqueFilename = uniqid() . '_' . $file->getClientOriginalName();
                    $message->attach($file->getRealPath(), [
                        'as' => $uniqueFilename,
                        'mime' => $file->getMimeType(),
                    ]);
                    }
                }
            });
            return response()->json(['success' => 1,
            'message' => 'Your suggestions for enhancement has been sent to the relevant team.']);
        } catch (\Exception $ex) {
            logger('==postEnhancementSuggestion-Exception-' . $ex->getMessage());
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    public function getReportIssueForm(Request $request)
    {
        return view('common.report_issue');
    }

    public function postReportIssueForm(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'screenshot.*' => 'mimes:jpeg,png,jpg,pdf|max:2048',
            ], [
                'screenshot.*.max' => 'Max allowed size is up to 2MB',
                'screenshot.*.mimes' => 'Uploaded file must be a file of type: jpeg, png, jpg, pdf.'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => 0, 'error' => $validator->errors()->all()]);
            }
            $data = [];
            $data['from_name']  =  auth()->user()->name;
            $data['from_email']  =  env('MAIL_FROM_ADDRESS');
            $data['reply_to'] = auth()->user()->email;
            $data['body']  = $request->explanation_detail;
            $data['subject']  = 'Issue - ' . $request->subject;
            $data['to_email']  = 'appsupport@angelsofcare.com';
            $data['file'] = $request->screenshot;
            Mail::send([], [], function ($message) use ($data, $request) {
                $message->from($data['from_email'], $data['from_name']);
                $message->subject($data['subject']);
                $message->replyTo($data['reply_to'], $data['from_name']);
                $message->to($data['to_email']);
                $message->html($data['body']);
                if (!empty($request->screenshot)) {
                    foreach ($request->screenshot as $file) {
                    $uniqueFilename = uniqid() . '_' . $file->getClientOriginalName();
                    $message->attach($file->getRealPath(), [
                        'as' => $uniqueFilename,
                        'mime' => $file->getMimeType(),
                    ]);
                    }
                }
            });
            return response()->json(['success' => 1, 'message' => 'Your issue has been sent to the relevant team.']);
        } catch (\Exception $ex) {
            logger('==postEnhancementSuggestion-Exception-' . $ex->getMessage());
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }
}
