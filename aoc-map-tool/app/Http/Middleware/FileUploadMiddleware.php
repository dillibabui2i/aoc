<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class FileUploadMiddleware
{
    protected $allowedFileTypes;

    public function handle(Request $request, Closure $next, ...$allowedFileTypes){
        $files = $request->file();
        $allowedMime = [];
        $allowedExtension = [];

        //collecting the mime types and the extension for that route ex: text/csv#csv here the # is the separator for mime and extension
        foreach ($allowedFileTypes as $key => $value) {
            $v = explode('#',$value);
            if(isset($v[0]) && $v[0] != ''){
                $allowedMime[] = $v[0];
            }
            if(isset($v[1]) && $v[1] != ''){
                $allowedExtension[] = $v[1];
            }
        }

        foreach ($files as $fileInputName => $file) {
            if ($file->isValid()) {
                //checking the mime type and the extension
                $mime = $file->getMimeType();
                $extension = $file->getClientOriginalExtension();
                if (!in_array($mime, $allowedMime) || (!empty($allowedExtension) && !in_array($extension, $allowedExtension))) {
                    $errMsg = 'Invalid file type. Allowed Mime types: ' .
                    implode(', ', $allowedMime);
                    if(!empty($allowedExtension)){
                        $errMsg .= ' and allowed extensions: '.
                        implode(', ', array_unique($allowedExtension));
                    }
                    if ($request->ajax()) {
                        return response()->json(['error' => $errMsg], 400);
                    }else{
                        Session::flash('error', $errMsg);
                        return redirect()->back()->withInput();
                    }
                }
            }else{
                $errMsg = 'Invalid file';
                if ($request->ajax()) {
                    return response()->json(['error' => $errMsg], 400);
                }else{
                    Session::flash('error', $errMsg);
                    return redirect()->back()->withInput();
                }
            }
        }
        return $next($request);
    }
}
