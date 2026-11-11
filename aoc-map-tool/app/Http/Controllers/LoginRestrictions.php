<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\LoginRestriction as Restriction;
use Illuminate\Http\Request;

class LoginRestrictions extends Controller
{
    public function ajaxLoginRestrictionsData(Request $request)
    {
        return $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'login_restrictions');
    }

    public function getLoginRestrictions(Request $request)
    {
        $dataTable = H::getHelper('Datatable')->getConfiguration('login_restrictions');

        return view('account.loginrestrictions', ['dataTable'=>$dataTable]);
    }

    public function destroy(Request $request)
    {
        try {
            Restriction::where('id', $request->input('id'))->delete();
        } catch (\Exception $e) {
            H::ExcelLog('Restriction destroy Exception '. $e->getMessage(), 'loginRestrictionDestroy.log');
        }

        return redirect(route('loginrestrictions'));
    }

    public function edit(Request $request)
    {
        try {
            $data = Restriction::where('id', $request->input('id'))->first();

            return view('account.restrictionform', ['data'=>$data]);
        } catch (\Exception $e) {
            return redirect(route('loginrestrictions'));
        }
    }

    public function save(Request $request)
    {
        try {
            $requestData = $request->all();
            if (isset($requestData['id']) && !empty($requestData['id'])) {
                $restriction = Restriction::find($requestData['id']);
            } else {
                $restriction = new Restriction;
            }

            $restriction->blacklisted = $requestData['blacklisted'];
            $restriction->whitelisted = $requestData['whitelisted'];
            $restriction->username = $requestData['username'];
            $restriction->ipaddress = $requestData['ipaddress'];

            $restriction->save();

            return redirect(route('loginrestrictions'));
        } catch (\Exception $e) {
            return redirect(route('loginrestrictions'));
        }
    }
}
