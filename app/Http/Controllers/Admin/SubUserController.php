<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubUser;
use App\Models\SubUserPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SubUserController extends Controller
{
        private const PERMISSIONS = [
        'dashboard' => 'Panel',
        'products' => 'Ürünler',
        'orders' => 'Siparişler',
        'orders.bulk_cargo_label_print' => 'Siparisler: Toplu Kargo Etiket Yazdirma',
        'customers' => 'Müşteriler',
        'reports' => 'Raporlar (Tümü)',
        'reports.orders' => 'Raporlar: Sipariş ve Ciro',
        'reports.top_products' => 'Raporlar: Çok Satan Ürünler',
        'reports.sold_products' => 'Raporlar: Sat???�lan Ürün Listesi',
        'reports.category_sales' => 'Raporlar: Kategori Bazl???� Sat???�ş',
        'reports.brand_sales' => 'Raporlar: Marka Bazl???� Sat???�ş',
        'reports.vat' => 'Raporlar: KDV Raporu',
        'reports.commission' => 'Raporlar: Komisyon Raporu',
        'reports.commission_tariffs' => 'Raporlar: Ürün Komisyon Tarifeleri',
        'reports.stock_value' => 'Raporlar: Stoktaki Ürün Tutarlar???�',
        'reports.profitability' => 'Raporlar: Sipariş Kârl???�l???�k Analizi',
        'reports.profit_engine' => 'Raporlar: Profit Engine',
        'reports.marketplace_risk' => 'Raporlar: Marketplace Risk',
        'reports.action_engine' => 'Raporlar: Action Engine',
        'control_tower' => 'Control Tower',
        'communication_center' => 'M?steri Iletisim Merkezi',
        'settlements.view' => 'Hakediş: Görüntüleme',
        'settlements.manage' => 'Hakediş: Yönetim',
        'integrations' => 'Entegrasyonlar',
        'addons' => 'Ek Modüller',
        'subscription' => 'Paketim',
        'settings' => 'Ayarlar',
        'help' => 'Yard???�m',
        'tickets' => 'Ticketlar',
        'invoices' => 'Faturalar',
    ];

    public function index(Request $request): View
    {
        $owner = $request->user();
        $subUsers = SubUser::query()
            ->where('owner_user_id', $owner->id)
            ->with('permissions')
            ->latest()
            ->get();

        return view('admin.sub-users.index', compact('subUsers'));
    }

    public function create(): View
    {
        $permissions = self::PERMISSIONS;

        return view('admin.sub-users.create', compact('permissions'));
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $owner = $request->user();

        $validated = $request->validate(['name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('sub_users', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
            'permissions' => 'array',
            'permissions.*' => [Rule::in(array_keys(self::PERMISSIONS))],
        ]);

        $subUser = SubUser::create([
            'owner_user_id' => $owner->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->syncPermissions($subUser, $validated['permissions'] ?? []);

        if ($request->ajax() || $request->expectsJson()) {
            return response()->json([
                'id' => $subUser->id,
                'name' => $subUser->name,
                'email' => $subUser->email,
                'is_active' => $subUser->is_active,
                'permissions' => $validated['permissions'] ?? [],
            ], 201);
        }

        return redirect()->route('portal.sub-users.index')
            ->with('success', 'Alt kullan???�c???� oluşturuldu.');
    }

    public function edit(Request $request, SubUser $subUser): View
    {
        $this->ensureOwner($request, $subUser);
        $permissions = self::PERMISSIONS;
        $selected = $subUser->permissions()->pluck('permission_key')->all();

        return view('admin.sub-users.edit', compact('subUser', 'permissions', 'selected'));
    }

    public function update(Request $request, SubUser $subUser): RedirectResponse
    {
        $this->ensureOwner($request, $subUser);

        $validated = $request->validate(['name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('sub_users', 'email')->ignore($subUser->id),
                Rule::unique('users', 'email'),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'is_active' => 'nullable|boolean',
            'permissions' => 'array',
            'permissions.*' => [Rule::in(array_keys(self::PERMISSIONS))],
        ]);

        $subUser->update(['name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? $subUser->password,
            'is_active' => array_key_exists('is_active', $validated)
                ? (bool) $validated['is_active']
                : (bool) $subUser->is_active,
        ]);

        $this->syncPermissions($subUser, $validated['permissions'] ?? []);

        return redirect()->route('portal.sub-users.index')
            ->with('success', 'Alt kullan???�c???� güncellendi.');
    }

    public function destroy(Request $request, SubUser $subUser): RedirectResponse
    {
        $this->ensureOwner($request, $subUser);
        $subUser->delete();

        return redirect()->route('portal.sub-users.index')
            ->with('success', 'Alt kullan???�c???� silindi.');
    }

    private function syncPermissions(SubUser $subUser, array $permissions): void
    {
        $subUser->permissions()->delete();

        $rows = array_map(function (string $permission) use ($subUser) {
            return [
                'sub_user_id' => $subUser->id,
                'permission_key' => $permission,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }, $permissions);

        if ($rows) {
            SubUserPermission::insert($rows);
        }
    }

    private function ensureOwner(Request $request, SubUser $subUser): void
    {
        $owner = $request->user();
        if ($subUser->owner_user_id !== $owner->id) {
            abort(403);
        }
    }
}






