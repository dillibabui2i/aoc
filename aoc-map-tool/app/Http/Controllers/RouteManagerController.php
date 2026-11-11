<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RouteManagerController extends Controller
{
    public function getMissingRoutes(Request $request)
    {
        $routeList = [];
        $routeList = \Route::getRoutes()->get();

        //var_dump($routeList);
        $routeArray = [];
        $dbRoute = ['ignition.healthCheck', 'ignition.executeSolution', 'ignition.shareReport', 'ignition.scripts', 'ignition.styles', 'login', 'logout', 'loginpost', 'forgotpassword', 'sendmail', 'reset.passsword', 'reset.password.post', 'restricted'];

        foreach ($routeList as $route) {
            if (!empty($route->getName())) {
                $routeArray[] = $route->getName();
            } //exit;
        }
        $getList = DB::select('select * from permissions');
        foreach ($getList as $route) {
            $dbRoute[] = $route->route_name;
            if (!empty($route->related_routes)) {
                $dbRoute = array_merge($dbRoute, explode(',', $route->related_routes));
            }
        }

        $newArray = array_diff($routeArray, $dbRoute);

        return view('routemanager.form', ['routes'=>$newArray]);
    }

    public function setRouteManager(Request $request)
    {
        $reqData = $request->all();
        $dbData = [];
        $dbData['group'] = $reqData['group'];
        $dbData['name'] = $reqData['name'];
        $dbData['route_name'] = $reqData['route_name'];
        $dbData['related_routes'] = $reqData['related_routes'];

        if (!empty($dbData['group']) && !empty($dbData['name']) && !empty($dbData['route_name'])) {
            DB::table('permissions')->insert($dbData);
            Cache::tags(['{permissions_cache}'])->flush();

            return redirect(route('missingroutes'))->withSuccess('Permission Created Successfully');
        }

        return redirect(route('missingroutes'))->withErrors('Please make sure you fill all the fields');
    }
}
