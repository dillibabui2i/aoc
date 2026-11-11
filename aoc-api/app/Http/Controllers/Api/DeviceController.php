<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\Helper;
use App\Models\Device;
use Validator;
class DeviceController extends Controller
{
    //
    /**
     * It is used to register device
     *
     */
    public function registerDevice(Request $request)
    {
        $validation = $this->validateRegisterDevice($request->all());

        if ($validation->fails()) {
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], $validation->errors()->all());
        }
        try {
            $device_id = Device::createOrUpdate($request->all());
            if ($device_id) {
                return Helper::response(config('constants.response_code.OK'), ['device_id' => $device_id], __('messages.device_registered'));
            } else {
                return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.device_registration_failed'));
            }
        } catch (\Exception $e) {
            Helper::ExcelLog('registerDevice Error: ' . $e->getMessage(), 'registerDevice.log');
            return Helper::response(config('constants.response_code.SOMETHING_WENT_WRONG'), [], __('messages.device_registration_failed'));
        }
    }

    /**
     * It is used to validate register device
     *
     */
    private function validateRegisterDevice($request)
    {

        return Validator::make(
            $request,
            array_merge(
                config('constants.validations.common.rules')
            ),
            array_merge(
                config('constants.validations.common.messages')
            )
        );
    }
}
