<?php

namespace App\Services\Entitlements;

use App\Models\Module;
use App\Models\User;
use App\Models\UserModule;
use Illuminate\Support\Carbon;
use RuntimeException;

class EntitlementService
{
    public function hasModule(User $user, string $code): bool
    {
        $code = trim($code);
        if ($code === '') {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        $plan = $user->getActivePlan();
        if (!$plan) {
            return false;
        }

        if ($plan->hasModule($code)) {
            return true;
        }

        return $this->hasActiveUserModule($user, $code);
    }

    /**
     * Grants a module entitlement to the given user by creating/updating a user_modules row.
     *
     * Note: If the related Module is inactive (modules.is_active=false), we still set the user_module to active,
     * but hasModule() will return false because it requires Module.is_active=true.
     */
    public function grantModule(
        User $user,
        string $code,
        ?Carbon $endsAt = null,
        array $meta = [],
        ?Carbon $startsAt = null
    ): UserModule {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('Module code is required.');
        }

        $module = Module::query()->where('code', $code)->first();
        if (!$module) {
            throw new RuntimeException("Module not found: {$code}");
        }

        $userModule = $user->userModules()
            ->where('module_id', $module->id)
            ->first();

        $effectiveStartsAt = $startsAt ?? ($userModule?->starts_at ?? Carbon::now());

        $effectiveEndsAt = $endsAt;
        if ($userModule?->ends_at && $endsAt) {
            $effectiveEndsAt = $endsAt->greaterThan($userModule->ends_at) ? $endsAt : $userModule->ends_at;
        } elseif ($userModule?->ends_at && !$endsAt) {
            $effectiveEndsAt = $userModule->ends_at;
        }

        $existingMeta = is_array($userModule?->meta) ? $userModule->meta : [];
        $mergedMeta = array_merge($existingMeta, $meta);
        if (empty($mergedMeta)) {
            $mergedMeta = null;
        }

        if ($userModule) {
            $userModule->status = 'active';
            $userModule->starts_at = $effectiveStartsAt;
            $userModule->ends_at = $effectiveEndsAt;
            $userModule->meta = $mergedMeta;
            $userModule->save();

            return $userModule;
        }

        return $user->userModules()->create([
            'module_id' => $module->id,
            'status' => 'active',
            'starts_at' => $effectiveStartsAt,
            'ends_at' => $effectiveEndsAt,
            'meta' => $mergedMeta,
        ]);
    }

    public function revokeModule(User $user, string $code, bool $hardDelete = false): void
    {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('Module code is required.');
        }

        $module = Module::query()->where('code', $code)->first();
        if (!$module) {
            return;
        }

        $userModule = $user->userModules()
            ->where('module_id', $module->id)
            ->first();

        if (!$userModule) {
            return;
        }

        if ($hardDelete) {
            $userModule->delete();
            return;
        }

        $userModule->status = 'inactive';
        $userModule->ends_at = Carbon::now();
        $userModule->save();
    }

    public function setModuleStatus(
        User $user,
        string $code,
        string $status,
        ?Carbon $endsAt = null,
        ?Carbon $startsAt = null,
        array $meta = []
    ): UserModule {
        $code = trim($code);
        if ($code === '') {
            throw new RuntimeException('Module code is required.');
        }

        if (!in_array($status, ['active', 'inactive', 'expired'], true)) {
            throw new RuntimeException('Invalid module status.');
        }

        $module = Module::query()->where('code', $code)->first();
        if (!$module) {
            throw new RuntimeException("Module not found: {$code}");
        }

        $userModule = $user->userModules()
            ->where('module_id', $module->id)
            ->first();

        $existingMeta = is_array($userModule?->meta) ? $userModule->meta : [];
        $mergedMeta = array_merge($existingMeta, $meta);
        if (empty($mergedMeta)) {
            $mergedMeta = null;
        }

        if ($userModule) {
            $userModule->status = $status;
            if ($startsAt) {
                $userModule->starts_at = $startsAt;
            } elseif (!$userModule->starts_at) {
                $userModule->starts_at = Carbon::now();
            }
            if ($endsAt !== null) {
                $userModule->ends_at = $endsAt;
            }
            $userModule->meta = $mergedMeta;
            $userModule->save();

            return $userModule;
        }

        return $user->userModules()->create([
            'module_id' => $module->id,
            'status' => $status,
            'starts_at' => $startsAt ?? Carbon::now(),
            'ends_at' => $endsAt,
            'meta' => $mergedMeta,
        ]);
    }

    private function hasActiveUserModule(User $user, string $code): bool
    {
        $now = Carbon::now();

        return $user->userModules()
            ->where('status', 'active')
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', $now);
            })
            ->whereHas('module', function ($q) use ($code) {
                $q->where('code', $code)->where('is_active', true);
            })
            ->exists();
    }
}
