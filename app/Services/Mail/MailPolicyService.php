<?php

namespace App\Services\Mail;

use App\Models\MailLog;
use App\Models\MailRuleAssignment;
use App\Models\MailTemplate;
use App\Models\User;
use Carbon\Carbon;

class MailPolicyService
{
    public const DECISION_SEND = 'send';
    public const DECISION_BLOCKED = 'blocked';
    public const DECISION_DEDUPED = 'deduped';
    public const DECISION_SKIPPED = 'skipped';

    public function canSend(string $key, User $user, array $meta = []): array
    {
        $template = MailTemplate::query()->where('key', $key)->first();
        if (!$template) {
            return $this->decision(self::DECISION_SKIPPED, 'template_missing');
        }

        if (!$template->enabled) {
            return $this->decision(self::DECISION_SKIPPED, 'template_disabled');
        }
$rule = $this->resolveRule($key, $user);
        if ($rule && $rule->allowed === false) {
            return $this->decision(self::DECISION_BLOCKED, 'rule_blocked');
        }

        if (!empty($meta['dedupe_key'])) {
            $exists = MailLog::query()
                ->where('key', $key)
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->whereJsonContains('metadata_json->dedupe_key', $meta['dedupe_key'])
                ->exists();

            if ($exists) {
                return $this->decision(self::DECISION_DEDUPED, 'dedupe_key');
            }
        }

        if ($rule) {
            $limitDecision = $this->checkLimits($key, $user, $rule->daily_limit, $rule->monthly_limit);
            if ($limitDecision !== null) {
                return $limitDecision;
            }
        }

        return $this->decision(self::DECISION_SEND, 'ok');
    }

    private function resolveRule(string $key, User $user): ?MailRuleAssignment
    {
        $rules = MailRuleAssignment::query()
            ->where('key', $key)
            ->where(function ($builder) use ($user): void {
                $builder->orWhere(function ($sub) use ($user): void {
                    $sub->where('scope_type', 'user')
                        ->where('scope_id', $user->id);
                });

                $planId = $user->subscription?->plan_id;
                if ($planId) {
                    $builder->orWhere(function ($sub) use ($planId): void {
                        $sub->where('scope_type', 'plan')
                            ->where('scope_id', $planId);
                    });
                }
$moduleIds = $user->modules()->pluck('modules.id')->all();
                if (!empty($moduleIds)) {
                    $builder->orWhere(function ($sub) use ($moduleIds): void {
                        $sub->where('scope_type', 'module')
                            ->whereIn('scope_id', $moduleIds);
                    });
                }
            })
            ->get();

        if ($rules->isEmpty()) {
            return null;
        }
$blocked = $rules->firstWhere('allowed', false);
        if ($blocked) {
            return $blocked;
        }

        return $rules->first();
    }

    private function checkLimits(string $key, User $user, ?int $dailyLimit, ?int $monthlyLimit): ?array
    {
        $now = Carbon::now();

        if ($dailyLimit !== null) {
            $count = MailLog::query()
                ->where('key', $key)
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->whereBetween('sent_at', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
                ->count();

            if ($count >= $dailyLimit) {
                return $this->decision(self::DECISION_BLOCKED, 'daily_limit');
            }
        }

        if ($monthlyLimit !== null) {
            $count = MailLog::query()
                ->where('key', $key)
                ->where('user_id', $user->id)
                ->where('status', 'success')
                ->whereBetween('sent_at', [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()])
                ->count();

            if ($count >= $monthlyLimit) {
                return $this->decision(self::DECISION_BLOCKED, 'monthly_limit');
            }
        }

        return null;
    }

    private function decision(string $decision, string $reason): array
    {
        return [
            'decision' => $decision,
            'reason' => $reason,
        ];
    }
}
