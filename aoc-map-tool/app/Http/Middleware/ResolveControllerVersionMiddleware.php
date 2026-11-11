<?php

namespace App\Http\Middleware;

use App\Helpers\LD;
use Closure;
use Illuminate\Support\Str;

class ResolveControllerVersionMiddleware
{
    /**
     * @param $request
     * @param Closure $next
     * @return mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next): mixed
    {
        $route = $request->route();
        $action = $route->getAction();

        if (!isset($action['controller'])) {
            return $next($request);
        }

        [$controller, $method] = explode('@', $action['controller']);
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $map = config('controllerVersions', []);

        foreach ($map as $v2Controller => $item) {
            if (!isset($item['methods']) || !is_array($item['methods'])) {
                continue;
            }

            if (!array_key_exists($method, $item['methods'])) {
                continue;
            }

            $baseName = class_basename($controller);
            $v2Base = class_basename($v2Controller);
            if ($baseName !== $v2Base) {
                continue;
            }

            $flags = $item['methods'][$method] ?? [];
            if (!is_array($flags) || empty($flags)) {
                continue;
            }

            $checkMode = $item['check'] ?? 'any';

            if ($checkMode === 'all') {
                $all = true;
                foreach ($flags as $f) {
                    if (!LD::checkFeatureFlag($f)) {
                        $all = false;
                        break;
                    }
                }

                $shouldReplace = $all;
            } else {
                $any = false;
                foreach ($flags as $f) {
                    if (LD::checkFeatureFlag($f)) {
                        $any = true;
                        break;
                    }
                }

                $shouldReplace = $any;
            }

            if ($shouldReplace) {
                $newUses = $v2Controller . '@' . $method;

                $action['uses'] = $newUses;
                $action['controller'] = $newUses;
                $action['namespace'] = (string) $v2Controller;
                $route->setAction($action);
                $route->controller = null;

                break;
            }
        }

        return $next($request);
    }
}
