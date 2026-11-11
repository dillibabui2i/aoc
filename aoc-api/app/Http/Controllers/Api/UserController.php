<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ExternalUsers;
use Validator;
use App\Helpers\Helper;

class UserController extends Controller
{
    //
    public function getUserDetails(Request $request)
    {
        try {
            $user = ExternalUsers::getUserDetails($request->id,['id','name','email','status']);
            if ($user == NULL) {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.invalid_user'));
            }
            return Helper::response(config('constants.response_code.OK'), ['user' => $user]);
        } catch (\Exception $e) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.something_went_wrong'));
        }
    }
}
