<?php

namespace App\Console\Commands;

use App\Domains\Settlements\Models\FeatureFlag;
use App\Models\Module;
use App\Models\User;
use App\Services\Entitlements\EntitlementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnableHakedisModuleCommand extends Command
{
    protected $signature = 'settlements:enable
        {--user-id= : Entitlement verilecek user id}
        {--email= : Entitlement verilecek user email}
        {--tenant-id= : Feature flag acilacak tenant id}
        {--grant-tenant-users : Tenant icindeki tum kullanicilara entitlement ver}
        {--no-grant : User entitlement verme, sadece modul/flag ac}
        {--module-only : Sadece module catalog kaydi olustur}
        {--flag-only : Sadece tenant feature flag ac}';

    protected $description = 'Enable Hakediş module in one command (module catalog + tenant flag + user entitlement).';

    public function handle(EntitlementService $entitlements): int
    {
        $moduleOnly = (bool) $this->option('module-only');
        $flagOnly = (bool) $this->option('flag-only');
        if ($moduleOnly && $flagOnly) {
            $this->error('--module-only ve --flag-only birlikte kullanilamaz.');
            return self::FAILURE;
        }

        if (!Schema::hasTable('modules')) {
            $this->error('modules tablosu bulunamadi. Once migration calistirin.');
            return self::FAILURE;
        }

        if (!$flagOnly) {
            Module::query()->updateOrCreate(
                ['code' => 'feature.hakedis'],
                [
                    'name' => 'Hakediş Kontrol Merkezi',
                    'description' => 'Payout, mutabakat ve sapma merkezi.',
                    'type' => 'feature',
                    'billing_type' => 'recurring',
                    'is_active' => true,
                    'sort_order' => 0,
                ]
            );
            $this->info('Module hazir: feature.hakedis');
        }

        $user = $this->resolveUser();
        $tenantId = $this->resolveTenantId($user);

        if (!$moduleOnly) {
            if (!$tenantId) {
                $this->error('Tenant id belirlenemedi. --tenant-id veya --user-id/--email verin.');
                return self::FAILURE;
            }
            $this->ensureTenantRow($tenantId, $user);
            if (!Schema::hasTable('feature_flags')) {
                $this->error('feature_flags tablosu bulunamadi. Once migration calistirin.');
                return self::FAILURE;
            }

            FeatureFlag::query()->withoutGlobalScope('tenant_scope')->updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => 'hakedis_module'],
                ['enabled' => true]
            );
            $this->info("Feature flag acildi: tenant_id={$tenantId}, key=hakedis_module");
        }

        if (!$moduleOnly && !$flagOnly && !$this->option('no-grant')) {
            if (!$user) {
                if ($tenantId && $this->option('grant-tenant-users')) {
                    $granted = 0;
                    $tenantUsers = User::query()
                        ->where(function ($q) use ($tenantId) {
                            $q->where('tenant_id', $tenantId)->orWhere('id', $tenantId);
                        })
                        ->get();
                    foreach ($tenantUsers as $tenantUser) {
                        $entitlements->grantModule($tenantUser, 'feature.hakedis');
                        $granted++;
                    }
                    $this->info("Tenant kullanicilarina entitlement verildi: {$granted} user");
                } else {
                    $this->warn('Kullanici bulunamadi, entitlement grant atlandi. --user-id/--email veya --grant-tenant-users kullanin.');
                }
            } else {
                $entitlements->grantModule($user, 'feature.hakedis');
                $this->info("Entitlement verildi: user_id={$user->id}, module=feature.hakedis");
            }
        }

        $this->line('Tamamlandi.');
        return self::SUCCESS;
    }

    private function resolveUser(): ?User
    {
        $userId = $this->option('user-id');
        if (is_numeric($userId)) {
            return User::query()->find((int) $userId);
        }

        $email = trim((string) $this->option('email'));
        if ($email !== '') {
            return User::query()->where('email', $email)->first();
        }

        return null;
    }

    private function resolveTenantId(?User $user): ?int
    {
        $tenant = $this->option('tenant-id');
        if (is_numeric($tenant)) {
            return (int) $tenant;
        }

        if (!$user) {
            return null;
        }

        return (int) ($user->tenant_id ?: $user->id);
    }

    private function ensureTenantRow(int $tenantId, ?User $user): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        $exists = DB::table('tenants')->where('id', $tenantId)->exists();
        if ($exists) {
            return;
        }

        $tenantName = trim((string) ($user?->name ?: $user?->email ?: 'Tenant ' . $tenantId));
        DB::table('tenants')->insert([
            'id' => $tenantId,
            'name' => $tenantName,
            'status' => 'active',
            'plan' => 'starter',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->warn("Tenant kaydi olusturuldu: id={$tenantId}, name={$tenantName}");
    }
}
