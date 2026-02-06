<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SetSessionCookieForSubdomain
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $host = $request->getHost();
        if ($host !== '') {
            $appDomain = config('app.app_domain');
            $saDomain = config('app.sa_domain');
            $appCookie = env('APP_SESSION_COOKIE', 'app_session');
            $saCookie = env('SA_SESSION_COOKIE', 'sa_session');

            if (($appDomain && $host === $appDomain) || Str::startsWith($host, 'app.')) {
                config(['session.cookie' => $appCookie]);
            } elseif (($saDomain && $host === $saDomain) || Str::startsWith($host, 'sa.')) {
                config(['session.cookie' => $saCookie]);
            }
        }

        return $next($request);
    }
}
