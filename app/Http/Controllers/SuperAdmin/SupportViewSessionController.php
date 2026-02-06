<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportAccessLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportViewSessionController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->get('status', 'active');
        $sourceType = $request->get('source_type');
        $search = trim((string) $request->get('q', ''));

        $query = SupportAccessLog::query()
            ->with(['actor', 'targetUser'])
            ->orderByDesc('started_at');

        if ($status === 'active') {
            $query->active();
        } elseif ($status === 'expired') {
            $query->expired();
        } elseif ($status === 'ended') {
            $query->whereNotNull('ended_at');
        }

        if ($sourceType && in_array($sourceType, ['manual', 'ticket'], true)) {
            $query->where('source_type', $sourceType);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->whereHas('actor', function ($actorQuery) use ($search): void {
                    $actorQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });

                $builder->orWhereHas('targetUser', function ($targetQuery) use ($search): void {
                    $targetQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });

                if (is_numeric($search)) {
                    $builder->orWhere(function ($sourceQuery) use ($search): void {
                        $sourceQuery->where('source_type', 'ticket')
                            ->where('source_id', (int) $search);
                    });
                }
            });
        }
$logs = $query->paginate(20)->withQueryString();

        return view('super-admin.support-view-sessions.index', compact('logs', 'status', 'sourceType', 'search'));
    }

    public function end(Request $request, SupportAccessLog $supportAccessLog): RedirectResponse
    {
        if ($supportAccessLog->ended_at === null) {
            $meta = $supportAccessLog->meta ?? [];
            if (!is_array($meta)) {
                $meta = [];
            }
$supportAccessLog->update([
                'ended_at' => now(),
                'meta' => array_merge($meta, [
                    'ended_by_user_id' => auth()->id(),
                    'ended_by_role' => auth()->user()?->role,
                ]),
            ]);
        }
$previousUrl = url()->previous();
        $fallbackUrl = route('super-admin.support-view-sessions.index');
        $redirectUrl = ($previousUrl && $previousUrl !== $request->fullUrl())
            ? $previousUrl
            : $fallbackUrl;

        return redirect()
            ->to($redirectUrl)
            ->with('success', 'Oturum kapatildi.');
    }
}
