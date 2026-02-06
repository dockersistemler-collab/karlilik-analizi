<?php

namespace App\Services\Admin;

use App\Models\SupportAccessEvent;
use Illuminate\Http\Request;

class SupportViewEventLogger
{
    public function logBlocked(Request $request, string $type): void
    {
        $payload = [
            'query_keys' => array_keys($request->query()),
            'input_keys' => array_keys($request->except(['password', 'token', '_token'])),
        ];

        try {
            SupportAccessEvent::create([
                'support_access_log_id' => session('support_view_log_id'),
                'actor_user_id' => auth()->id(),
                'target_user_id' => session('support_view_target_user_id'),
                'type' => $type,
                'method' => $request->method(),
                'route_name' => $request->route()?->getName(), 'url' => $request->fullUrl(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'payload' => $payload,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return;
        }
    }
}
