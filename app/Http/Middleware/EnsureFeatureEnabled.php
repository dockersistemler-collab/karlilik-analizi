<?php

namespace App\Http\Middleware;

use App\Services\Features\FeatureGate;
use App\Support\SupportUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFeatureEnabled
{
    public function __construct(private readonly FeatureGate $features)
    {
    }

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = SupportUser::currentUser();
        if (!$user) {
            return $next($request);
        }

        if (!$this->features->enabled($feature, $user)) {
            return redirect()
                ->route('portal.upgrade', ['feature' => $feature])
                ->with('error', 'Bu özellik mevcut planınızda yok.');
        }

        return $next($request);
    }
}


