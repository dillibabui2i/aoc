<?php

namespace App\Http\Middleware;

use App\Helpers\H;
use App\Helpers\LD;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTrachVentClassCancellationAllowed
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class') && H::canPermissions('trachventclass.destroyOccurrence')) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['message' => 'Forbidden.'], Response::HTTP_FORBIDDEN);
        }

        return redirect(route('restricted'))->withErrors('This action is unauthorized.');
    }
}
