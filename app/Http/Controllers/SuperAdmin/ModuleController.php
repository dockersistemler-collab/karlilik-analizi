<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\User;
use App\Models\UserModule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function index(Request $request): View
    {
        $code = trim((string) $request->query('code', ''));

        $modules = Module::query()
            ->when($code !== '', fn ($q) => $q->where('code', $code))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('super-admin.modules.index', compact('modules', 'code'));
    }

    public function create(): View
    {
        $module = new Module([
            'is_active' => true,
            'type' => 'feature',
            'billing_type' => 'recurring',
            'sort_order' => 0,
        ]);

        return view('super-admin.modules.create', compact('module'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(['code' => 'required|string|max:255|regex:/^[a-z0-9_.-]+$/|unique:modules,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => 'required|in:integration,feature',
            'billing_type' => 'required|in:one_time,recurring,usage',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $module = Module::create($validated);

        return redirect()
            ->route('super-admin.modules.edit', $module)
            ->with('success', 'Modül oluşturuldu.');
    }

    public function edit(Module $module): View
    {
        $clients = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get();

        $userModules = UserModule::query()
            ->with('user')
            ->where('module_id', $module->id)
            ->orderByDesc('id')
            ->get();

        return view('super-admin.modules.edit', compact('module', 'clients', 'userModules'));
    }

    public function update(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate(['code' => 'required|string|max:255|regex:/^[a-z0-9_.-]+$/|unique:modules,code,' . $module->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'type' => 'required|in:integration,feature',
            'billing_type' => 'required|in:one_time,recurring,usage',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $module->update($validated);

        return back()->with('success', 'Modül güncellendi.');
    }

    public function destroy(Module $module): RedirectResponse
    {
        $module->delete();

        return redirect()
            ->route('super-admin.modules.index')
            ->with('success', 'Modül silindi.');
    }

    public function assignToUser(Request $request, Module $module): RedirectResponse
    {
        $validated = $request->validate(['user_id' => 'required|exists:users,id',
            'status' => 'required|in:active,inactive,expired',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'meta' => 'nullable|string|max:10000',
        ]);

        $meta = null;
        if (!empty($validated['meta'])) {
            $decoded = json_decode($validated['meta'], true);
            if (!is_array($decoded)) {
                return back()->with('info', 'Meta alanı geçerli bir JSON olmalı.');
            }
$meta = $decoded;
        }

        UserModule::query()->updateOrCreate(
            [
                'user_id' => (int) $validated['user_id'],
                'module_id' => $module->id,
            ],
            [
                'status' => $validated['status'],
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'meta' => $meta,
            ]
        );

        return back()->with('success', 'Kullanıcıya modül atandı.');
    }

    public function toggleUserModule(UserModule $userModule): RedirectResponse
    {
        $userModule->status = $userModule->status === 'active' ? 'inactive' : 'active';
        $userModule->save();

        return back()->with('success', 'Modül durumu güncellendi.');
    }

    public function removeUserModule(UserModule $userModule): RedirectResponse
    {
        $userModule->delete();

        return back()->with('success', 'Modül ataması kaldırıldı.');
    }
    public function toggle(Module $module): RedirectResponse
    {
        $module->is_active = !$module->is_active;
        $module->save();

        return back()->with('success', 'Modül durumu güncellendi.');
    }
}

