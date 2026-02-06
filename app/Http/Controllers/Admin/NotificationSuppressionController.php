<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailSuppression;
use App\Services\Notifications\EmailSuppressionService;
use App\Support\SupportUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSuppressionController
{
    public function index(Request $request): View
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $query = EmailSuppression::query()->where('tenant_id', $user->id);

        if ($request->filled('search')) {
            $search = strtolower(trim((string) $request->input('search')));
            $query->where('email', 'like', "%{$search}%");
        }

        if ($request->filled('reason')) {
            $query->where('reason', $request->string('reason'));
        }
$suppressions = $query->latest('created_at')->paginate(20)->withQueryString();

        return view('admin.notification-hub.suppressions.index', [
            'suppressions' => $suppressions,
            'reasons' => ['bounce', 'complaint', 'manual', 'invalid', 'hard_fail'],
            'canGlobal' => $user->isSuperAdmin(),
        ]);
    }

    public function store(Request $request, EmailSuppressionService $service): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        $data = $request->validate(['email' => ['required', 'email', 'max:255'],
            'scope' => ['required', 'in:tenant,global'],
            'reason' => ['required', 'in:manual,invalid'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        if ($data['scope'] === 'global' && !$user->isSuperAdmin()) {
            abort(403);
        }
$tenantId = $data['scope'] === 'global' ? null : $user->id;
        $meta = [];
        if (!empty($data['note'])) {
            $meta['note'] = $data['note'];
        }
$service->suppress($tenantId, $data['email'], $data['reason'], 'admin', $meta);

        return back()->with('success', 'Suppression kaydi olusturuldu.');
    }

    public function destroy(EmailSuppression $suppression): RedirectResponse
    {
        $user = SupportUser::currentUser();
        abort_unless($user, 403);

        if ($suppression->tenant_id !== $user->id && !$user->isSuperAdmin()) {
            abort(404);
        }
$suppression->delete();

        return back()->with('success', 'Suppression kaldirildi.');
    }
}
