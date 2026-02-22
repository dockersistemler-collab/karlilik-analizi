<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin@pazaryeri.com'],
            [
                'tenant_id' => null,
                'name' => 'Super Admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', '12345678')),
                'role' => 'super_admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $role = Role::findOrCreate('SuperAdmin', 'sanctum');
        $user->syncRoles([$role]);
    }
}
