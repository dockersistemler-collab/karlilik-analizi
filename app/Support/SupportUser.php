<?php

namespace App\Support;

use App\Domain\Tickets\Models\Ticket;
use App\Models\SupportAccessLog;
use App\Services\Admin\SupportViewService;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class SupportUser
{
    private static ?User $cachedTarget = null;

    public static function forgetCachedTarget(): void
    {
        self::$cachedTarget = null;
    }

    public static function isEnabled(): bool
    {
        $authUser = Auth::user();
        if (!$authUser || !in_array($authUser->role, ['super_admin', 'support_agent'], true)) {
            return false;
        }

        if (!session('support_view_enabled')) {
            return false;
        }
$expiresAt = session('support_view_expires_at');
        if ($expiresAt) {
            $expires = Carbon::parse($expiresAt);
            if (now()->greaterThan($expires)) {
                app(SupportViewService::class)->stop();
                return false;
            }
        }
$targetId = session('support_view_target_user_id');
        if (!$targetId) {
            return false;
        }

        if (!self::targetUser()) {
            return false;
        }
$logId = session('support_view_log_id');
        if ($logId) {
            $log = SupportAccessLog::query()->find($logId);
            if (!$log) {
                app(SupportViewService::class)->stop();
                return false;
            }
            if ($log->ended_at) {
                app(SupportViewService::class)->stop();
                return false;
            }
        }
$target = self::targetUser();
        if (!$target || $target->isSuperAdmin() || !$target->is_active) {
            app(SupportViewService::class)->stop();
            return false;
        }
$sourceType = session('support_view_source_type');
        if ($sourceType === 'ticket') {
            $ticketId = session('support_view_source_id');
            if (!$ticketId) {
                app(SupportViewService::class)->stop();
                return false;
            }
$ticket = Ticket::query()->find($ticketId);
            if (!$ticket) {
                app(SupportViewService::class)->stop();
                return false;
            }

            if ((int) $ticket->customer_id !== (int) $targetId) {
                app(SupportViewService::class)->stop();
                return false;
            }

            if ($authUser->role === 'support_agent' && (int) $ticket->assigned_to_id !== (int) $authUser->id) {
                app(SupportViewService::class)->stop();
                return false;
            }

            if (!in_array($ticket->status, [
                Ticket::STATUS_OPEN,
                Ticket::STATUS_WAITING_CUSTOMER,
                Ticket::STATUS_WAITING_ADMIN,
            ], true)) {
                app(SupportViewService::class)->stop();
                return false;
            }
        }

        return true;
    }

    public static function currentUser(): ?User
    {
        if (self::isEnabled()) {
            return self::targetUser();
        }

        return Auth::user();
    }

    public static function targetUser(): ?User
    {
        if (self::$cachedTarget !== null) {
            return self::$cachedTarget;
        }
$targetId = session('support_view_target_user_id');
        if (!$targetId) {
            return null;
        }

        self::$cachedTarget = User::query()->find($targetId);

        return self::$cachedTarget;
    }

    public static function maskText(?string $value, int $visibleStart = 1, int $visibleEnd = 1): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }
$value = (string) $value;
        $length = strlen($value);
        if ($length <= $visibleStart + $visibleEnd) {
            return str_repeat('*', $length);
        }
$start = substr($value, 0, $visibleStart);
        $end = substr($value, -$visibleEnd);
        $masked = str_repeat('*', max(2, $length - $visibleStart - $visibleEnd));

        return $start.$masked.$end;
    }

    public static function maskEmail(?string $email): ?string
    {
        if ($email === null || $email === '') {
            return $email;
        }
$parts = explode('@', $email, 2);
        if (count($parts) !== 2) {
            return self::maskText($email, 1, 1);
        }
$local = self::maskText($parts[0], 1, 1);
        $domainParts = explode('.', $parts[1], 2);
        $domain = self::maskText($domainParts[0], 1, 0);
        $tld = $domainParts[1] ?? '';

        return $tld !== '' ? $local.'@'.$domain.'.'.$tld : $local.'@'.$domain;
    }

    public static function maskPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '') {
            return $phone;
        }
$digits = preg_replace('/\D+/', '', $phone);
        if ($digits === '') {
            return self::maskText($phone, 1, 1);
        }
$visible = substr($digits, -2);
        return str_repeat('*', max(4, strlen($digits) - 2)).$visible;
    }
}
