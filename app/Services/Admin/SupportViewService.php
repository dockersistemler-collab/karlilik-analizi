<?php

namespace App\Services\Admin;

use App\Domain\Tickets\Models\Ticket;
use App\Events\SupportViewStarted;
use App\Models\SupportAccessLog;
use App\Models\User;
use App\Notifications\SupportViewStartedNotification;
use App\Support\SupportUser;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

class SupportViewService
{
    public function start(User $superAdmin, User $target, string $reason, array $meta = []): void
    {
        // TODO: Enforce 2FA for support view when support.mfa_required is enabled.
        if (!$superAdmin->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'super_admin_id' => 'Only super admin can start support view.',
            ]);
        }

        if ($target->isSuperAdmin()) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Target user cannot be a super admin.',
            ]);
        }

        if (!$target->is_active) {
            throw ValidationException::withMessages([
                'target_user_id' => 'Target user is not active.',
            ]);
        }
$reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Reason is required.',
            ]);
        }
$expiresAt = now()->addMinutes((int) config('support.ttl_minutes', 60));

        $log = SupportAccessLog::create([
            'super_admin_id' => $superAdmin->id,
            'actor_user_id' => $superAdmin->id,
            'actor_role' => 'super_admin',
            'source_type' => 'manual',
            'source_id' => null,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'scope' => 'read_only',
            'meta' => $meta,
        ]);

        event(new SupportViewStarted(
            $log->id,
            $target->id,
            $superAdmin->id,
            $reason,
            $log->started_at->toDateTimeString()
        ));

        Session::put('support_view_enabled', true);
        Session::put('support_view_actor_user_id', $superAdmin->id);
        Session::put('support_view_target_user_id', $target->id);
        Session::put('support_view_log_id', $log->id);
        Session::put('support_view_expires_at', $expiresAt->toIso8601String());
        Session::put('support_view_source_type', 'manual');
        Session::put('support_view_source_id', null);
        Session::regenerate();

        if (config('support.notify_user', true)) {
            $target->notify(new SupportViewStartedNotification($log, $superAdmin));
        }
    }

    public function stop(): void
    {
        if (!session('support_view_enabled')) {
            return;
        }
$logId = session('support_view_log_id');
        if ($logId) {
            SupportAccessLog::query()->whereKey($logId)->update(['ended_at' => now(),
            ]);
        }

        Session::forget([
            'support_view_enabled',
            'support_view_actor_user_id',
            'support_view_target_user_id',
            'support_view_log_id',
            'support_view_expires_at',
            'support_view_source_type',
            'support_view_source_id',
        ]);
        SupportUser::forgetCachedTarget();
        Session::regenerate();
    }

    public function startForTicket(
        User $actor,
        User $target,
        Ticket $ticket,
        string $note = '',
        array $meta = []
    ): void {
        if (!in_array($actor->role, ['super_admin', 'support_agent'], true)) {
            throw ValidationException::withMessages([
                'actor' => 'Only super admin or support agent can start support view for tickets.',
            ]);
        }

        if ((int) $ticket->customer_id !== (int) $target->id) {
            throw ValidationException::withMessages([
                'ticket' => 'Ticket customer does not match target user.',
            ]);
        }

        if ($actor->role === 'support_agent' && (int) $ticket->assigned_to_id !== (int) $actor->id) {
            throw ValidationException::withMessages([
                'actor' => 'Support agent is not assigned to this ticket.',
            ]);
        }

        if (!in_array($ticket->status, [
            Ticket::STATUS_OPEN,
            Ticket::STATUS_WAITING_CUSTOMER,
            Ticket::STATUS_WAITING_ADMIN,
        ], true)) {
            throw ValidationException::withMessages([
                'status' => 'Ticket is not active.',
            ]);
        }
$expiresAt = now()->addMinutes((int) config('support.ttl_minutes', 60));
        $note = trim($note);
        $reason = $note === '' ? 'Ticket #'.$ticket->id : 'Ticket #'.$ticket->id.' - '.$note;
        $meta = array_merge([
            'ticket_id' => $ticket->id,
            'ticket_status' => $ticket->status,
        ], $meta);

        $log = SupportAccessLog::create([
            'super_admin_id' => $actor->role === 'super_admin' ? $actor->id : null,
            'actor_user_id' => $actor->id,
            'actor_role' => $actor->role,
            'source_type' => 'ticket',
            'source_id' => $ticket->id,
            'target_user_id' => $target->id,
            'started_at' => now(),
            'expires_at' => $expiresAt,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'scope' => 'read_only',
            'meta' => $meta,
        ]);

        Session::put('support_view_enabled', true);
        Session::put('support_view_actor_user_id', $actor->id);
        Session::put('support_view_target_user_id', $target->id);
        Session::put('support_view_log_id', $log->id);
        Session::put('support_view_expires_at', $expiresAt->toIso8601String());
        Session::put('support_view_source_type', 'ticket');
        Session::put('support_view_source_id', $ticket->id);
        Session::regenerate();

        if (config('support.notify_user_on_ticket', true)) {
            $target->notify(new SupportViewStartedNotification($log, $actor));
        }
    }
}
