<?php

namespace App\Services\Features;

use App\Models\User;
use App\Services\SystemSettings\SettingsRepository;

class FeatureGate
{
    public function __construct(private readonly SettingsRepository $settings)
    {
    }

    public function enabled(string $feature, User|int $tenant): bool
    {
        $planCode = $this->planCodeForTenant($tenant);
        $matrix = $this->planMatrix();

        $features = $matrix[$planCode] ?? $matrix[config('features.default_plan', 'free')] ?? [];
        if (!is_array($features)) {
            $features = [];
        }

        if (in_array('*', $features, true)) {
            return true;
        }

        return in_array($feature, $features, true);
    }

    public function allForPlan(string $planCode): array
    {
        $matrix = $this->planMatrix();

        return array_values($matrix[$planCode] ?? []);
    }

    public function allFeatures(): array
    {
        $labels = config('features.feature_labels', []);
        if (is_array($labels) && $labels !== []) {
            return array_keys($labels);
        }

        return [
            'health_dashboard',
            'health_notifications',
            'incidents',
            'incident_sla',
            'mail_settings',
        ];
    }

    public function featureLabels(): array
    {
        $labels = config('features.feature_labels', []);
        if (!is_array($labels)) {
            return [];
        }

        return $labels;
    }

    public function featureDescriptions(): array
    {
        $descriptions = config('features.feature_descriptions', []);
        if (!is_array($descriptions)) {
            return [];
        }

        return $descriptions;
    }

    public function planCodeForTenant(User|int $tenant): string
    {
        $user = $tenant instanceof User ? $tenant : User::query()->find($tenant);
        if (!$user) {
            return config('features.default_plan', 'free');
        }
$defaultPlan = (string) config('features.default_plan', 'free');
        $tenantUser = $this->resolveTenantUser($user);
        $tenantPlanCode = is_string($tenantUser?->plan_code ?? null) ? strtolower(trim((string) $tenantUser->plan_code)) : null;
        $hasSubscription = (bool) $tenantUser?->getActivePlan();

        if ($tenantPlanCode !== null && $tenantPlanCode !== '') {
            if ($tenantPlanCode !== $defaultPlan || !$hasSubscription) {
                return $this->resolvePlanAlias($tenantPlanCode);
            }
        }
$authUser = auth()->user();
        $authPlanCode = is_string($authUser?->plan_code ?? null) ? strtolower(trim((string) $authUser->plan_code)) : null;
        if ($authPlanCode !== null && $authPlanCode !== '') {
            if ($authPlanCode !== $defaultPlan || !$hasSubscription) {
                return $this->resolvePlanAlias($authPlanCode);
            }
        }
$plan = $tenantUser?->getActivePlan();
$planCode = $plan?->slug ?: $plan?->name ?: null;
        $planCode = is_string($planCode) ? strtolower(trim($planCode)) : null;

        if (!$planCode) {
            return $defaultPlan;
        }

        return $this->resolvePlanAlias($planCode);
    }

    private function resolveTenantUser(User $user): User
    {
        if (method_exists($user, 'tenant')) {
            $tenant = $user->tenant;
            if ($tenant instanceof User) {
                return $tenant;
            }
        }

        if (property_exists($user, 'tenant_id') && $user->tenant_id) {
            $tenant = User::query()->find($user->tenant_id);
            if ($tenant) {
                return $tenant;
            }
        }

        return $user;
    }

    private function resolvePlanAlias(string $planCode): string
    {
        $aliases = config('features.plan_aliases', []);
        if (is_array($aliases) && array_key_exists($planCode, $aliases)) {
            return (string) $aliases[$planCode];
        }

        return $planCode;
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function planMatrix(): array
    {
        $raw = $this->settings->get('features', 'plan_matrix', null);
        $decoded = null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        if (!is_array($decoded)) {
            $decoded = config('features.plan_matrix', []);
        }

        return $this->normalizeMatrix($decoded);
    }

    /**
     * @param array<string,mixed> $matrix
     * @return array<string,array<int,string>>
     */
    private function normalizeMatrix(array $matrix): array
    {
        $normalized = [];
        foreach ($matrix as $plan => $features) {
            if (!is_string($plan)) {
                continue;
            }
            if (!is_array($features)) {
                $features = [];
            }
$normalized[$plan] = array_values(array_filter($features, fn ($value) => is_string($value) && $value !== ''));
        }

        return $normalized;
    }
}
