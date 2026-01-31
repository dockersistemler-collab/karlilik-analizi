<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Marketplace;
use App\Models\ReferralProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(Request $request): View
    {
        $program = ReferralProgram::query()->latest()->first();
        $marketplaces = Marketplace::query()->orderBy('name')->get();
        $users = User::query()
            ->where('role', 'client')
            ->orderBy('name')
            ->get();
        $selectedUserId = $request->query('user_id');
        $selectedUser = null;

        if ($selectedUserId) {
            $selectedUser = User::query()
                ->where('role', 'client')
                ->whereKey($selectedUserId)
                ->first();
        }

        if (!$selectedUser) {
            $selectedUser = $users->first();
        }

        $reportExportsEnabled = (bool) AppSetting::getValue('reports_exports_enabled', true);
        $categoryMappingEnabled = (bool) AppSetting::getValue('category_mapping_enabled', true);
        $categoryMappingInlineEnabled = (bool) AppSetting::getValue('category_mapping_inline_enabled', true);
        $categoryMappingAutoSyncEnabled = (bool) AppSetting::getValue('category_mapping_auto_sync_enabled', true);
        $categoryImportOnlyLeafDefault = (bool) AppSetting::getValue('category_import_only_leaf_default', true);
        $categoryImportCreateMappingsDefault = (bool) AppSetting::getValue('category_import_create_mappings_default', true);
        $vatColorsRaw = AppSetting::getValue('vat_report_marketplace_colors', '{}');
        $vatColors = json_decode($vatColorsRaw, true);
        if (!is_array($vatColors)) {
            $vatColors = [];
        }
        $quickActionsConfig = $this->loadQuickActionsConfig();
        $quickActionOptions = $this->quickActionOptions();
        $quickActionRoles = $this->quickActionRoles();
        $quickActionIcons = $this->quickActionIcons();
        $panelThemeFontOptions = [
            'poppins' => 'Poppins',
            'manrope' => 'Manrope',
            'space_grotesk' => 'Space Grotesk',
            'system' => 'Sistem Varsayılanı',
        ];
        $panelThemeFont = (string) AppSetting::getValue('panel_theme_font', 'poppins');
        if (!array_key_exists($panelThemeFont, $panelThemeFontOptions)) {
            $panelThemeFont = 'poppins';
        }
        $panelThemeAccent = (string) AppSetting::getValue('panel_theme_accent', '#ff4439');
        if (!preg_match('/^#[0-9a-fA-F]{6}$/', $panelThemeAccent)) {
            $panelThemeAccent = '#ff4439';
        }
        $panelThemeRadius = (int) AppSetting::getValue('panel_theme_radius', 5);
        if ($panelThemeRadius < 0) {
            $panelThemeRadius = 0;
        }
        if ($panelThemeRadius > 16) {
            $panelThemeRadius = 16;
        }

        return view('super-admin.settings.index', compact(
            'program',
            'users',
            'selectedUser',
            'reportExportsEnabled',
            'categoryMappingEnabled',
            'categoryMappingInlineEnabled',
            'categoryMappingAutoSyncEnabled',
            'categoryImportOnlyLeafDefault',
            'categoryImportCreateMappingsDefault',
            'marketplaces',
            'vatColors',
            'quickActionsConfig',
            'quickActionOptions',
            'quickActionRoles',
            'quickActionIcons',
            'panelThemeFontOptions',
            'panelThemeFont',
            'panelThemeAccent',
            'panelThemeRadius'
        ));
    }

    public function updateReferralProgram(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
            'referrer_reward_type' => 'required|in:percent,duration',
            'referrer_reward_value' => 'nullable|numeric|min:0',
            'referred_reward_type' => 'required|in:percent,duration',
            'referred_reward_value' => 'nullable|numeric|min:0',
            'max_uses_per_referrer_per_year' => 'required|integer|min:0',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ReferralProgram::query()->where('is_active', true)->update(['is_active' => false]);

        ReferralProgram::create($validated);

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Tavsiye programı güncellendi.');
    }

    public function updateClientSettings(Request $request, User $user): RedirectResponse
    {
        if (!$user->isClient()) {
            return redirect()->route('super-admin.settings.index')
                ->with('info', 'Sadece müşteri hesapları güncellenebilir.');
        }

        $validated = $request->validate([
            'company_name' => 'nullable|string|max:255',
            'company_slogan' => 'nullable|string|max:255',
            'company_phone' => 'nullable|string|max:50',
            'notification_email' => 'nullable|email|max:255',
            'company_address' => 'nullable|string|max:2000',
            'company_website' => 'nullable|url|max:255',
            'invoice_number_tracking' => 'nullable|boolean',
            'company_logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('company_logo')) {
            if ($user->company_logo_path) {
                Storage::disk('public')->delete($user->company_logo_path);
            }
            $path = $request->file('company_logo')->store('company-logos', 'public');
            $validated['company_logo_path'] = $path;
        }

        $validated['invoice_number_tracking'] = $request->boolean('invoice_number_tracking');
        $user->update($validated);

        return redirect()->route('super-admin.settings.index', ['user_id' => $user->id])
            ->with('success', 'Müşteri ayarları güncellendi.');
    }

    public function updateReportExports(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('reports_exports_enabled');
        AppSetting::setValue('reports_exports_enabled', $enabled);

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Rapor dışa aktarma ayarı güncellendi.');
    }
    public function updateVatColors(Request $request): RedirectResponse
    {
        $colors = $request->input('vat_colors', []);
        $payload = [];

        foreach ($colors as $marketplaceId => $color) {
            $color = trim((string) $color);
            if ($color === '') {
                continue;
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                return redirect()->route('super-admin.settings.index')
                    ->with('info', 'KDV renkleri iÃ§in geÃ§erli HEX formatÄ± kullanÄ±n (Ã¶rn: #ff4439).');
            }
            $payload[$marketplaceId] = $color;
        }

        AppSetting::setValue('vat_report_marketplace_colors', json_encode($payload));

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'KDV raporu renkleri gÃ¼ncellendi.');
    }

    public function updateQuickActions(Request $request): RedirectResponse
    {
        $allowed = array_keys($this->quickActionOptions());
        $actions = $request->input('actions', []);
        $payload = [];
        $index = 0;

        foreach ($actions as $action) {
            $key = $action['key'] ?? null;
            if (!$key || !in_array($key, $allowed, true)) {
                continue;
            }
            $enabled = (bool) ($action['enabled'] ?? false);
            $icon = $action['icon'] ?? 'fa-bolt';
            $color = $action['color'] ?? '#ff4439';
            $roles = $action['roles'] ?? [];
            if (!is_array($roles)) {
                $roles = [];
            }
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
                $color = '#ff4439';
            }
            $payload[] = [
                'key' => $key,
                'enabled' => $enabled,
                'icon' => $icon,
                'color' => $color,
                'roles' => array_values(array_filter($roles)),
                'order' => $index++,
            ];
        }

        AppSetting::setValue('admin_quick_actions_v2', json_encode($payload));

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'HÄ±zlÄ± menÃ¼ ayarlarÄ± gÃ¼ncellendi.');
    }

    public function updateCategoryMappingSettings(Request $request): RedirectResponse
    {
        $request->validate([
            'category_mapping_enabled' => 'nullable|boolean',
            'category_mapping_inline_enabled' => 'nullable|boolean',
            'category_mapping_auto_sync_enabled' => 'nullable|boolean',
            'category_import_only_leaf_default' => 'nullable|boolean',
            'category_import_create_mappings_default' => 'nullable|boolean',
        ]);

        AppSetting::setValue('category_mapping_enabled', $request->boolean('category_mapping_enabled'));
        AppSetting::setValue('category_mapping_inline_enabled', $request->boolean('category_mapping_inline_enabled'));
        AppSetting::setValue('category_mapping_auto_sync_enabled', $request->boolean('category_mapping_auto_sync_enabled'));
        AppSetting::setValue('category_import_only_leaf_default', $request->boolean('category_import_only_leaf_default'));
        AppSetting::setValue('category_import_create_mappings_default', $request->boolean('category_import_create_mappings_default'));

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Kategori eşitleme ayarları güncellendi.');
    }

    public function updateTheme(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'panel_theme_font' => 'required|string|in:poppins,manrope,space_grotesk,system',
            'panel_theme_accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'panel_theme_radius' => 'required|integer|min:0|max:16',
        ]);

        AppSetting::setValue('panel_theme_font', $validated['panel_theme_font']);
        AppSetting::setValue('panel_theme_accent', $validated['panel_theme_accent']);
        AppSetting::setValue('panel_theme_radius', (int) $validated['panel_theme_radius']);

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Panel gÃ¶rÃ¼nÃ¼m ayarlarÄ± gÃ¼ncellendi.');
    }

    private function quickActionOptions(): array
    {
        return [
            'invoices.create' => [
                'label' => 'Fatura Ekle',
                'route' => 'admin.invoices.create',
            ],
            'categories.create' => [
                'label' => 'Kategori Ekle',
                'route' => 'admin.categories.create',
            ],
            'brands.create' => [
                'label' => 'Marka Ekle',
                'route' => 'admin.brands.create',
            ],
            'products.create' => [
                'label' => 'Ürün Ekle',
                'route' => 'admin.products.create',
            ],
        ];
    }

    private function quickActionRoles(): array
    {
        return [
            'client' => 'Müşteri',
            'subuser' => 'Alt Kullanıcı',
        ];
    }

    private function quickActionIcons(): array
    {
        return [
            'fa-bolt',
            'fa-file-invoice',
            'fa-tags',
            'fa-certificate',
            'fa-box',
            'fa-clipboard-check',
            'fa-bullhorn',
            'fa-plus',
        ];
    }

    private function loadQuickActionsConfig(): array
    {
        $options = $this->quickActionOptions();
        $roles = array_keys($this->quickActionRoles());
        $raw = AppSetting::getValue('admin_quick_actions_v2', null);
        if ($raw) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $normalized = [];
                foreach ($decoded as $row) {
                    $key = $row['key'] ?? null;
                    if (!$key || !isset($options[$key])) {
                        continue;
                    }
                    $normalized[] = [
                        'key' => $key,
                        'label' => $options[$key]['label'],
                        'enabled' => (bool) ($row['enabled'] ?? false),
                        'icon' => $row['icon'] ?? 'fa-bolt',
                        'color' => $row['color'] ?? '#ff4439',
                        'roles' => array_values(array_intersect($roles, $row['roles'] ?? $roles)),
                        'order' => (int) ($row['order'] ?? 0),
                    ];
                }
                usort($normalized, fn ($a, $b) => $a['order'] <=> $b['order']);
                if ($normalized) {
                    return $normalized;
                }
            }
        }

        $legacyRaw = AppSetting::getValue('admin_quick_actions', '[]');
        $legacySelected = json_decode($legacyRaw, true);
        if (!is_array($legacySelected)) {
            $legacySelected = [];
        }
        $defaults = [];
        foreach (array_keys($options) as $index => $key) {
            $defaults[] = [
                'key' => $key,
                'label' => $options[$key]['label'],
                'enabled' => in_array($key, $legacySelected, true),
                'icon' => 'fa-bolt',
                'color' => '#ff4439',
                'roles' => $roles,
                'order' => $index,
            ];
        }

        return $defaults;
    }
}
