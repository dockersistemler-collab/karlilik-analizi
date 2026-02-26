<?php

namespace App\Domains\Settlements\Services;

use App\Domains\Settlements\Models\ReconciliationRule;
use Illuminate\Database\Eloquent\Builder;

class TenantRuleResolver
{
    public function tolerance(int $tenantId, string $marketplace, ?float $fallback = null): float
    {
        $tenantRule = $this->activeRuleQuery($marketplace, 'tolerance', $tenantId)
            ->where('key', 'default')
            ->first();

        if ($tenantRule) {
            return round((float) data_get($tenantRule->value, 'amount', 0.01), 4);
        }

        $globalRule = $this->activeRuleQuery($marketplace, 'tolerance', null)
            ->first();

        if ($globalRule) {
            return round((float) data_get($globalRule->value, 'amount', 0.01), 4);
        }

        return round((float) ($fallback ?? 0.01), 4);
    }

    /**
     * @return array<string,string>
     */
    public function mapRowTypeRules(int $tenantId, string $marketplace): array
    {
        $tenantRules = $this->activeRuleQuery($marketplace, 'map_row_type', $tenantId)->get();
        if ($tenantRules->isNotEmpty()) {
            return $tenantRules->mapWithKeys(function (ReconciliationRule $rule): array {
                $from = strtoupper((string) data_get($rule->value, 'from', $rule->key));
                $to = strtolower((string) data_get($rule->value, 'to', 'other'));
                return [$from => $to];
            })->all();
        }

        return $this->activeRuleQuery($marketplace, 'map_row_type', null)
            ->get()
            ->mapWithKeys(function (ReconciliationRule $rule): array {
                $from = strtoupper((string) data_get($rule->value, 'from', $rule->key));
                $to = strtolower((string) data_get($rule->value, 'to', 'other'));
                return [$from => $to];
            })->all();
    }

    public function lossThreshold(int $tenantId, string $marketplace, string $key, float $fallback): float
    {
        $tenantRule = $this->activeRuleQuery($marketplace, 'loss_rule', $tenantId)
            ->where('key', $key)
            ->first();
        if ($tenantRule) {
            return (float) data_get($tenantRule->value, 'threshold', $fallback);
        }

        $globalRule = $this->activeRuleQuery($marketplace, 'loss_rule', null)
            ->where('key', $key)
            ->first();
        if ($globalRule) {
            return (float) data_get($globalRule->value, 'threshold', $fallback);
        }

        return $fallback;
    }

    private function activeRuleQuery(string $marketplace, string $ruleType, ?int $tenantId): Builder
    {
        return ReconciliationRule::query()
            ->withoutGlobalScope('tenant_scope')
            ->where('marketplace', $marketplace)
            ->where('rule_type', $ruleType)
            ->where('is_active', true)
            ->when($tenantId !== null, function (Builder $q) use ($tenantId): void {
                $q->where('tenant_id', $tenantId)
                    ->where(function (Builder $sq): void {
                        $sq->where('scope', 'tenant')
                            ->orWhere('scope_type', 'tenant');
                    });
            }, function (Builder $q): void {
                $q->where(function (Builder $sq): void {
                    $sq->whereNull('tenant_id')
                        ->where(function (Builder $s2): void {
                            $s2->whereNull('scope')
                                ->orWhere('scope', 'global')
                                ->orWhere('scope_type', 'global');
                        });
                });
            })
            ->where(function (Builder $q): void {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
            })
            ->where(function (Builder $q): void {
                $q->whereNull('valid_to')->orWhere('valid_to', '>=', now());
            })
            ->orderByDesc('priority');
    }
}
