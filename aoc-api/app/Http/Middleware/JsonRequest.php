<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\Helper;

class JsonRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
//        if (!$request->secure()) {
//            return Helper::response(config('constants.response_code.INVALID_TOKEN'), [], 'Invalid Request!');
//            //return redirect()->secure($request->getRequestUri());
//        }

        if($request->header('Content-Type') == "text/plain"){
            $request->request->replace($request->json()->all());
        }
        return $next($request);
    }
}
