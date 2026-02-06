<?php

namespace Tests\Feature\Session;

use App\Http\Middleware\SetSessionCookieForSubdomain;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubdomainSessionCookieTest extends TestCase
{
    public function test_app_subdomain_uses_app_session_cookie(): void
    {
        Route::get('/__session-cookie', function () {
            return response()->json([
                'cookie' => config('session.cookie'),
                'host' => request()->getHost(),
            ]);
        })->middleware(SetSessionCookieForSubdomain::class);

        $appDomain = config('app.app_domain');

        $response = $this->get("http://{$appDomain}/__session-cookie");

        $response->assertOk();
        $response->assertJson([
            'cookie' => 'app_session',
            'host' => $appDomain,
        ]);
    }

    public function test_sa_subdomain_uses_sa_session_cookie(): void
    {
        Route::get('/__session-cookie', function () {
            return response()->json([
                'cookie' => config('session.cookie'),
                'host' => request()->getHost(),
            ]);
        })->middleware(SetSessionCookieForSubdomain::class);

        $saDomain = config('app.sa_domain');

        $response = $this->get("http://{$saDomain}/__session-cookie");

        $response->assertOk();
        $response->assertJson([
            'cookie' => 'sa_session',
            'host' => $saDomain,
        ]);
    }
}
