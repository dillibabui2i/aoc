<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class XSS
{
    public function handle(Request $request, Closure $next)
    {
        $excludeKey = ['email_body', 'signature', 'explanation_detail',
            'mfa_setup_content', 'mfa_otp_content', 'login_content', 'consentContent'];
        if ($request->all()) {
            $userInput = $request->all();
            array_walk_recursive($userInput, function (&$userInput,$key) use ($excludeKey) {
                if(!empty($userInput) && !in_array($key,$excludeKey)){
                    $userInput = strip_tags($userInput);
                }
            });
            $request->merge($userInput);
        }

        $route = $request->route();
        if ($route instanceof Route && $route->hasParameters()) {
            $userInput = $route->parameters();
            array_walk_recursive($userInput, function (&$userInput,$key) use ($excludeKey) {
                if(!empty($userInput) && !in_array($key,$excludeKey)){
                    $userInput = strip_tags($userInput);
                }
            });
            foreach($userInput as $key => $value) {
                $route->setParameter($key, $value);
            }
        }

        return $next($request);
    }
}
