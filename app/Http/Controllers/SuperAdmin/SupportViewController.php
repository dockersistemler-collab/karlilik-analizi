<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Domain\Tickets\Models\Ticket;
use App\Models\User;
use App\Services\Admin\SupportViewService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SupportViewController extends Controller
{
    public function start(Request $request, User $user, SupportViewService $service): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'max:255'],
        ]);

        $service->start($request->user(), $user, $validated['reason'], [
            'source' => 'super-admin-users',
        ]);

        return redirect()
            ->route('portal.dashboard')
            ->with('info', 'Support View baslatildi.');
    }

    public function stop(Request $request, SupportViewService $service): RedirectResponse
    {
        $service->stop();

        return redirect()
            ->route('super-admin.users.index')
            ->with('success', 'Support View sonlandirildi.');
    }

    public function startTicket(Request $request, Ticket $ticket, SupportViewService $service): RedirectResponse
    {
        $validated = $request->validate(['note' => ['nullable', 'string', 'max:255'],
        ]);

        $actor = $request->user();
        $target = $ticket->customer;

        $service->startForTicket($actor, $target, $ticket, $validated['note'] ?? '', [
            'source' => 'ticket',
        ]);

        return redirect()
            ->route('portal.dashboard')
            ->with('info', 'Support View baslatildi.');
    }
}


