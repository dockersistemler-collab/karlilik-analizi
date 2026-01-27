<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureReportExportsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = AppSetting::getValue('reports_exports_enabled', true);

        if (!$enabled) {
            abort(403, 'Rapor dışa aktarma özelliği kapalı.');
        }

        return $next($request);
    }
}
