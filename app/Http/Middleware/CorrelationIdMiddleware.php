<?php

namespace App\Http\Middleware;

use App\Support\CorrelationId;
use Closure;
use Illuminate\Http\Request;

class CorrelationIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $header = (string) $request->header('X-Correlation-Id', '');
        $id = $header !== '' ? $header : null;
        $id = CorrelationId::set($id);
        $request->attributes->set('correlation_id', $id);

        return $next($request);
    }
}
