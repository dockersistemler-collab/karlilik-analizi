<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RbacPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'tenants.manage',
            'features.manage',
            'users.manage',
            'roles.manage',
            'marketplace_accounts.manage',
            'settlement_rules.manage',
            'sync.run',
            'payouts.view',
            'payouts.reconcile',
            'disputes.view',
            'disputes.manage',
            'exports.create',
            'dashboard.view',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'sanctum');
        }

        $superAdmin = Role::findOrCreate('SuperAdmin', 'sanctum');
        $tenantAdmin = Role::findOrCreate('TenantAdmin', 'sanctum');
        $finance = Role::findOrCreate('Finance', 'sanctum');
        $viewer = Role::findOrCreate('Viewer', 'sanctum');

        $superAdmin->syncPermissions($permissions);
        $tenantAdmin->syncPermissions([
            'users.manage',
            'roles.manage',
            'marketplace_accounts.manage',
            'settlement_rules.manage',
            'sync.run',
            'payouts.view',
            'payouts.reconcile',
            'disputes.view',
            'disputes.manage',
            'exports.create',
            'dashboard.view',
        ]);
        $finance->syncPermissions([
            'sync.run',
            'payouts.view',
            'payouts.reconcile',
            'disputes.view',
            'disputes.manage',
            'exports.create',
            'dashboard.view',
        ]);
        $viewer->syncPermissions([
            'payouts.view',
            'disputes.view',
            'dashboard.view',
        ]);

        User::query()->where('role', 'super_admin')->get()->each(function (User $user) use ($superAdmin): void {
            $user->syncRoles([$superAdmin]);
        });
        User::query()->where('role', 'tenant_admin')->get()->each(function (User $user) use ($tenantAdmin): void {
            $user->syncRoles([$tenantAdmin]);
        });
        User::query()->where('role', 'finance')->get()->each(function (User $user) use ($finance): void {
            $user->syncRoles([$finance]);
        });
        User::query()->where('role', 'viewer')->get()->each(function (User $user) use ($viewer): void {
            $user->syncRoles([$viewer]);
        });
    }
}

