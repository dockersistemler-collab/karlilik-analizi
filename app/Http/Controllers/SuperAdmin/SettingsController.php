<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\Marketplace;
use App\Models\Plan;
use App\Models\ReferralProgram;
use App\Models\User;
use App\Mail\SystemSettingsTestMail;
use App\Services\Billing\Iyzico\Subscription\IyzicoSubscriptionClient;
use App\Services\Features\FeatureGate;
use App\Services\SystemSettings\SettingsRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
$mailSettings = $this->loadMailSettings(app(SettingsRepository::class));
        $incidentSlaSettings = $this->loadIncidentSlaSettings(app(SettingsRepository::class));
        $integrationHealthSettings = $this->loadIntegrationHealthSettings(app(SettingsRepository::class));
        $featureGate = app(FeatureGate::class);
        $featureMatrix = $this->loadFeatureMatrix(app(SettingsRepository::class));
        $featurePlans = $this->loadFeaturePlans($featureMatrix);
        $featureLabels = $featureGate->featureLabels();
        $featureKeys = $featureGate->allFeatures();
        $billingPlansCatalog = $this->loadBillingPlansCatalog(app(SettingsRepository::class), $featureGate);
        $iyzicoSettings = $this->loadIyzicoSettings(app(SettingsRepository::class));
        $dunningSettings = $this->loadDunningSettings(app(SettingsRepository::class));

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
            'panelThemeRadius',
            'mailSettings',
            'incidentSlaSettings',
            'integrationHealthSettings',
            'featureMatrix',
            'featurePlans',
            'featureLabels',
            'featureKeys',
            'billingPlansCatalog',
            'iyzicoSettings',
            'dunningSettings'
        ));
    }

    public function updateReferralProgram(Request $request): RedirectResponse
    {
        $validated = $request->validate(['name' => 'required|string|max:255',
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
                ->with('info', 'Sadece Müşteri hesapları güncellenebilir.');
        }
$validated = $request->validate(['company_name' => 'nullable|string|max:255',
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
                    ->with('info', 'KDV renkleri için geçerli HEX formatı kullanın (örn: #ff4439).');
            }
$payload[$marketplaceId] = $color;
        }

        AppSetting::setValue('vat_report_marketplace_colors', json_encode($payload));

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'KDV raporu renkleri güncellendi.');
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
            ->with('success', 'Hızlı menü ayarları güncellendi.');
    }

    public function updateCategoryMappingSettings(Request $request): RedirectResponse
    {
        $request->validate(['category_mapping_enabled' => 'nullable|boolean',
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
        $validated = $request->validate(['panel_theme_font' => 'required|string|in:poppins,manrope,space_grotesk,system',
            'panel_theme_accent' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'panel_theme_radius' => 'required|integer|min:0|max:16',
        ]);

        AppSetting::setValue('panel_theme_font', $validated['panel_theme_font']);
        AppSetting::setValue('panel_theme_accent', $validated['panel_theme_accent']);
        AppSetting::setValue('panel_theme_radius', (int) $validated['panel_theme_radius']);

        return redirect()->route('super-admin.settings.index')
            ->with('success', 'Panel görünüm ayarları güncellendi.');
    }

    public function updateMailSettings(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $validated = $request->validate(['override_enabled' => 'nullable|boolean',
            'from_name' => 'nullable|string|max:255',
            'from_address' => 'nullable|email|max:255',
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1|max:65535',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string|max:255',
            'smtp_encryption' => 'nullable|in:tls,ssl,none',
            'default_quiet_hours_start' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'default_quiet_hours_end' => ['required', 'regex:/^\d{2}:\d{2}$/'],
            'critical_email_default_enabled' => 'nullable|boolean',
        ]);

        $start = $validated['default_quiet_hours_start'] ?? '22:00';
        $end = $validated['default_quiet_hours_end'] ?? '08:00';
        if (!$this->isValidTimeString($start) || !$this->isValidTimeString($end)) {
            return redirect()->route('super-admin.settings.index', ['tab' => 'mail'])
                ->with('error', 'Quiet hours formatı HH:MM olmalı (örn: 22:00).');
        }
$userId = $request->user()?->id;
$settings->set('mail', 'override_enabled', $request->boolean('override_enabled'), false, $userId);
        $settings->set('mail', 'from.name', $validated['from_name'] ?? '', false, $userId);
        $settings->set('mail', 'from.address', $validated['from_address'] ?? '', false, $userId);
        $settings->set('mail', 'smtp.host', $validated['smtp_host'] ?? '', false, $userId);
        $settings->set('mail', 'smtp.port', $validated['smtp_port'] ?? '', false, $userId);
        $settings->set('mail', 'smtp.username', $validated['smtp_username'] ?? '', false, $userId);
        $settings->set('mail', 'smtp.encryption', $validated['smtp_encryption'] ?? 'tls', false, $userId);
        $settings->set('mail', 'default_quiet_hours_start', $start, false, $userId);
        $settings->set('mail', 'default_quiet_hours_end', $end, false, $userId);
        $settings->set('mail', 'critical_email_default_enabled', $request->boolean('critical_email_default_enabled'), false, $userId);

        if ($request->filled('smtp_password')) {
            $settings->set('mail', 'smtp.password', $validated['smtp_password'], true, $userId);
        }

        return redirect()->route('super-admin.settings.index', ['tab' => 'mail'])
            ->with('success', 'Mail ayarları güncellendi.');
    }

    public function sendTestMail(Request $request): RedirectResponse
    {
        $validated = $request->validate(['to_email' => 'required|email|max:255',
        ]);

        $user = $request->user();

        try {
            Mail::to($validated['to_email'])->send(new SystemSettingsTestMail($user));
            Log::info('system_settings.test_mail_sent', [
                'to' => $validated['to_email'],
                'actor_user_id' => $user?->id,
            ]);

            return redirect()->route('super-admin.settings.index', ['tab' => 'mail'])
            ->with('success', 'Test mail gönderildi.');
        } catch (\Throwable $exception) {
            Log::error('system_settings.test_mail_failed', [
                'to' => $validated['to_email'],
                'actor_user_id' => $user?->id, 'error' => $exception->getMessage(),
            ]);

            return redirect()->route('super-admin.settings.index', ['tab' => 'mail'])
                ->with('error', 'Test mail gönderilemedi.');
        }
    }

    public function updateIncidentSlaSettings(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $validated = $request->validate(['ack_sla_minutes' => 'required|integer|min:1|max:10080',
            'resolve_sla_minutes' => 'required|integer|min:1|max:10080',
        ]);

        $userId = $request->user()?->id;
$settings->set('incident_sla', 'ack_sla_minutes', (int) $validated['ack_sla_minutes'], false, $userId);
        $settings->set('incident_sla', 'resolve_sla_minutes', (int) $validated['resolve_sla_minutes'], false, $userId);

        return redirect()->route('super-admin.settings.index', ['tab' => 'incident-sla'])
            ->with('success', 'Incident SLA ayarları güncellendi.');
    }

    public function updateIntegrationHealthSettings(Request $request, SettingsRepository $settings): RedirectResponse
    {
        $validated = $request->validate(['stale_minutes' => 'required|integer|min:1|max:1440',
            'window_hours' => 'required|integer|min:1|max:168',
            'degraded_error_threshold' => 'required|integer|min:0|max:1000',
            'down_requires_critical' => 'nullable|boolean',
        ]);

        $userId = $request->user()?->id;
$settings->set('integration_health', 'stale_minutes', (int) $validated['stale_minutes'], false, $userId);
        $settings->set('integration_health', 'window_hours', (int) $validated['window_hours'], false, $userId);
        $settings->set('integration_health', 'degraded_error_threshold', (int) $validated['degraded_error_threshold'], false, $userId);
        $settings->set('integration_health', 'down_requires_critical', $request->boolean('down_requires_critical'), false, $userId);

        return redirect()->route('super-admin.settings.index', ['tab' => 'health'])
            ->with('success', 'Integration Health ayarları güncellendi.');
    }

    public function updateFeatureMatrix(Request $request, SettingsRepository $settings, FeatureGate $features): RedirectResponse
    {
        $validated = $request->validate(['plan_code' => 'required|string|max:100',
            'features' => 'array',
            'features.*' => 'string',
        ]);

        $planCode = strtolower(trim((string) $validated['plan_code']));
        $allowed = $features->allFeatures();
        $selected = array_values(array_filter($validated['features'] ?? [], fn ($value) => in_array($value, $allowed, true)));

        $matrix = $this->loadFeatureMatrix($settings);
        $matrix[$planCode] = $selected;

        $settings->set('features', 'plan_matrix', json_encode($matrix), false, $request->user()?->id);

        return redirect()->route('super-admin.settings.index', ['tab' => 'features'])
            ->with('success', 'Feature plan matrisi güncellendi.');
    }

    public function updateBillingPlansCatalog(Request $request, SettingsRepository $settings, FeatureGate $features): RedirectResponse
    {
        $validated = $request->validate(['plans' => 'required|array',
            'plans.*.name' => 'required|string|max:100',
            'plans.*.price_monthly' => 'required|integer|min:0|max:1000000',
            'plans.*.recommended' => 'nullable|boolean',
            'plans.*.contact_sales' => 'nullable|boolean',
            'plans.*.features' => 'array',
            'plans.*.features.*' => 'string',
            'plans.*.iyzico.productReferenceCode' => 'nullable|string|max:255',
            'plans.*.iyzico.pricingPlanReferenceCode' => 'nullable|string|max:255',
            'iyzico_enabled' => 'nullable|boolean',
            'iyzico_sandbox' => 'nullable|boolean',
            'iyzico_api_key' => 'nullable|string|max:255',
            'iyzico_secret_key' => 'nullable|string|max:255',
            'iyzico_webhook_secret' => 'nullable|string|max:255',
            'dunning_grace_days' => 'nullable|integer|min:0|max:365',
            'dunning_send_reminders' => 'nullable|boolean',
            'dunning_reminder_day_1' => 'nullable|integer|min:0|max:30',
            'dunning_reminder_day_2' => 'nullable|integer|min:0|max:30',
            'dunning_auto_downgrade' => 'nullable|boolean',
        ]);

        $allowed = $features->allFeatures();
        $normalized = [];

        foreach (($validated['plans'] ?? []) as $planCode => $plan) {
            if (!is_string($planCode) || !is_array($plan)) {
                continue;
            }
$code = strtolower(trim($planCode));
            $featuresList = $plan['features'] ?? [];
            if (!is_array($featuresList)) {
                $featuresList = [];
            }
$normalized[$code] = [
                'name' => (string) ($plan['name'] ?? $code),
                'price_monthly' => (int) ($plan['price_monthly'] ?? 0),
                'features' => array_values(array_filter($featuresList, fn ($value) => is_string($value) && in_array($value, $allowed, true))),
                'recommended' => (bool) ($plan['recommended'] ?? false),
                'contact_sales' => (bool) ($plan['contact_sales'] ?? false),
                'iyzico' => [
                    'productReferenceCode' => (string) ($plan['iyzico']['productReferenceCode'] ?? ''),
                    'pricingPlanReferenceCode' => (string) ($plan['iyzico']['pricingPlanReferenceCode'] ?? ''),
                ],
            ];
        }

        if ($normalized === []) {
            return redirect()->route('super-admin.settings.index', ['tab' => 'billing'])
                ->with('error', 'Kaydedilecek plan bulunamadı.');
        }
$settings->set('billing', 'plans_catalog', json_encode($normalized), false, $request->user()?->id);
$settings->set('billing', 'iyzico.enabled', $request->boolean('iyzico_enabled'), false, $request->user()?->id);
$settings->set('billing', 'iyzico.sandbox', $request->boolean('iyzico_sandbox'), false, $request->user()?->id);

        if ($request->filled('iyzico_api_key')) {
            $settings->set('billing', 'iyzico.api_key', $validated['iyzico_api_key'], true, $request->user()?->id);
        }
        if ($request->filled('iyzico_secret_key')) {
            $settings->set('billing', 'iyzico.secret_key', $validated['iyzico_secret_key'], true, $request->user()?->id);
        }
        if ($request->filled('iyzico_webhook_secret')) {
            $settings->set('billing', 'iyzico.webhook_secret', $validated['iyzico_webhook_secret'], true, $request->user()?->id);
        }
        $settings->set('billing', 'dunning.grace_days', (int) ($validated['dunning_grace_days'] ?? 3), false, $request->user()?->id);
        $settings->set('billing', 'dunning.send_reminders', $request->boolean('dunning_send_reminders', true), false, $request->user()?->id);
        $settings->set('billing', 'dunning.reminder_day_1', (int) ($validated['dunning_reminder_day_1'] ?? 0), false, $request->user()?->id);
        $settings->set('billing', 'dunning.reminder_day_2', (int) ($validated['dunning_reminder_day_2'] ?? 2), false, $request->user()?->id);
        $settings->set('billing', 'dunning.auto_downgrade', $request->boolean('dunning_auto_downgrade', true), false, $request->user()?->id);

        return redirect()->route('super-admin.settings.index', ['tab' => 'billing'])
            ->with('success', 'Plan kataloğu güncellendi.');
    }

    public function createIyzicoProduct(Request $request, SettingsRepository $settings, IyzicoSubscriptionClient $client): RedirectResponse|JsonResponse
    {
        $planCode = $this->resolvePlanCode($request);
        $catalog = $this->loadBillingPlansCatalogRaw($settings);
        $plan = $catalog[$planCode] ?? null;
        if (!$plan) {
            return $this->respondIyzicoAction($request, 'Plan bulunamad?.', 404);
        }
        $existing = (string) data_get($plan, 'iyzico.productReferenceCode', '');
        if ($existing !== '') {
            return $this->respondIyzicoAction($request, 'Iyzico product reference zaten var.', 200, [
                'productReferenceCode' => $existing,
            ]);
        }

        if (!$this->isIyzicoReady($settings)) {
            return $this->respondIyzicoAction($request, 'Iyzico API bilgileri eksik ya da devre disi.', 422);
        }
        $name = (string) ($plan['name'] ?? strtoupper($planCode));
        $reference = $client->createProduct($name);
        if ($reference === '') {
            return $this->respondIyzicoAction($request, 'Iyzico urun olusturulamadi.', 422);
        }

        data_set($plan, 'iyzico.productReferenceCode', $reference);
        $catalog[$planCode] = $plan;
        $settings->set('billing', 'plans_catalog', json_encode($catalog), false, $request->user()?->id);

        return $this->respondIyzicoAction($request, 'Iyzico urun olusturuldu.', 200, [
            'productReferenceCode' => $reference,
        ]);
    }

    public function createIyzicoPricingPlan(Request $request, SettingsRepository $settings, IyzicoSubscriptionClient $client): RedirectResponse|JsonResponse
    {
        $planCode = $this->resolvePlanCode($request);
        $catalog = $this->loadBillingPlansCatalogRaw($settings);
        $plan = $catalog[$planCode] ?? null;
        if (!$plan) {
            return $this->respondIyzicoAction($request, 'Plan bulunamad?.', 404);
        }
        $productRef = (string) data_get($plan, 'iyzico.productReferenceCode', '');
        if ($productRef === '') {
            return $this->respondIyzicoAction($request, 'Once Iyzico urun olusturmalisiniz.', 422);
        }
        $existing = (string) data_get($plan, 'iyzico.pricingPlanReferenceCode', '');
        if ($existing !== '') {
            return $this->respondIyzicoAction($request, 'Iyzico pricing plan reference zaten var.', 200, [
                'productReferenceCode' => $productRef,
                'pricingPlanReferenceCode' => $existing,
            ]);
        }

        if (!$this->isIyzicoReady($settings)) {
            return $this->respondIyzicoAction($request, 'Iyzico API bilgileri eksik ya da devre disi.', 422);
        }
        $name = (string) ($plan['name'] ?? strtoupper($planCode));
        $price = (float) ($plan['price_monthly'] ?? 0);
        $reference = $client->createPricingPlan($productRef, $name, $price);
        if ($reference === '') {
            return $this->respondIyzicoAction($request, 'Iyzico pricing plan olusturulamadi.', 422);
        }

        data_set($plan, 'iyzico.pricingPlanReferenceCode', $reference);
        $catalog[$planCode] = $plan;
        $settings->set('billing', 'plans_catalog', json_encode($catalog), false, $request->user()?->id);

        return $this->respondIyzicoAction($request, 'Iyzico pricing plan olusturuldu.', 200, [
            'productReferenceCode' => $productRef,
            'pricingPlanReferenceCode' => $reference,
        ]);
    }

    private function loadMailSettings(SettingsRepository $settings): array
    {
        $overrideEnabled = filter_var($settings->get('mail', 'override_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $encryption = $settings->get('mail', 'smtp.encryption', config('mail.mailers.smtp.encryption'));
        if ($encryption === null || $encryption === '') {
            $encryption = 'none';
        }

        return [
            'override_enabled' => (bool) $overrideEnabled,
            'from_name' => $settings->get('mail', 'from.name', config('mail.from.name')),
            'from_address' => $settings->get('mail', 'from.address', config('mail.from.address')),
            'smtp_host' => $settings->get('mail', 'smtp.host', config('mail.mailers.smtp.host')),
            'smtp_port' => $settings->get('mail', 'smtp.port', config('mail.mailers.smtp.port')),
            'smtp_username' => $settings->get('mail', 'smtp.username', config('mail.mailers.smtp.username')),
            'smtp_encryption' => $encryption,
            'default_quiet_hours_start' => $settings->get('mail', 'default_quiet_hours_start', '22:00'),
            'default_quiet_hours_end' => $settings->get('mail', 'default_quiet_hours_end', '08:00'),
            'critical_email_default_enabled' => filter_var(
                $settings->get('mail', 'critical_email_default_enabled', true),
                FILTER_VALIDATE_BOOLEAN
            ),
        ];
    }

    private function loadIncidentSlaSettings(SettingsRepository $settings): array
    {
        return [
            'ack_sla_minutes' => (int) $settings->get('incident_sla', 'ack_sla_minutes', config('incident_sla.ack_sla_minutes', 30)),
            'resolve_sla_minutes' => (int) $settings->get('incident_sla', 'resolve_sla_minutes', config('incident_sla.resolve_sla_minutes', 240)),
        ];
    }

    private function loadIntegrationHealthSettings(SettingsRepository $settings): array
    {
        return [
            'stale_minutes' => (int) $settings->get('integration_health', 'stale_minutes', config('integration_health.stale_minutes', 30)),
            'window_hours' => (int) $settings->get('integration_health', 'window_hours', config('integration_health.window_hours', 24)),
            'degraded_error_threshold' => (int) $settings->get('integration_health', 'degraded_error_threshold', config('integration_health.degraded_error_threshold', 1)),
            'down_requires_critical' => filter_var(
                $settings->get('integration_health', 'down_requires_critical', config('integration_health.down_requires_critical', true)),
                FILTER_VALIDATE_BOOLEAN
            ),
        ];
    }

    private function loadBillingPlansCatalog(SettingsRepository $settings, FeatureGate $features): array
    {
        $raw = $settings->get('billing', 'plans_catalog', null);
        $decoded = null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        if (!is_array($decoded)) {
            $decoded = config('billing.plans_catalog', []);
        }
$allowed = $features->allFeatures();
        $normalized = [];
        foreach ($decoded as $planCode => $plan) {
            if (!is_string($planCode) || !is_array($plan)) {
                continue;
            }
$code = strtolower(trim($planCode));
            $featuresList = $plan['features'] ?? [];
            if (!is_array($featuresList)) {
                $featuresList = [];
            }
$normalized[$code] = [
                'name' => (string) ($plan['name'] ?? $code),
                'price_monthly' => (int) ($plan['price_monthly'] ?? 0),
                'features' => array_values(array_filter($featuresList, fn ($value) => is_string($value) && in_array($value, $allowed, true))),
                'recommended' => (bool) ($plan['recommended'] ?? false),
                'contact_sales' => (bool) ($plan['contact_sales'] ?? false),
                'iyzico' => [
                    'productReferenceCode' => (string) data_get($plan, 'iyzico.productReferenceCode', ''),
                    'pricingPlanReferenceCode' => (string) data_get($plan, 'iyzico.pricingPlanReferenceCode', ''),
                ],
            ];
        }

        if ($normalized === []) {
            $normalized = config('billing.plans_catalog', []);
        }

        return $normalized;
    }

    private function loadBillingPlansCatalogRaw(SettingsRepository $settings): array
    {
        $raw = $settings->get('billing', 'plans_catalog', null);
        $decoded = null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        if (!is_array($decoded)) {
            $decoded = config('billing.plans_catalog', []);
        }

        return $decoded;
    }

    private function resolvePlanCode(Request $request): string
    {
        $planCode = strtolower(trim((string) $request->input('plan_code', '')));
        if ($planCode === '') {
            abort(422, 'Plan code is required.');
        }

        return $planCode;
    }

    private function isIyzicoReady(SettingsRepository $settings): bool
    {
        $enabled = filter_var($settings->get('billing', 'iyzico.enabled', false), FILTER_VALIDATE_BOOLEAN);
        $apiKey = (string) $settings->get('billing', 'iyzico.api_key', '');
        $secretKey = (string) $settings->get('billing', 'iyzico.secret_key', '');

        return $enabled && $apiKey !== '' && $secretKey !== '';
    }

    private function respondIyzicoAction(Request $request, string $message, int $status, array $data = []): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(array_merge(['message' => $message], $data), $status);
        }
$flash = $status >= 400 ? 'error' : 'success';
        if ($status === 200 && str_contains($message, 'zaten')) {
            $flash = 'info';
        }

        return redirect()->route('super-admin.settings.index', ['tab' => 'billing'])
            ->with($flash, $message);
    }

    private function loadIyzicoSettings(SettingsRepository $settings): array
    {
        return [
            'enabled' => filter_var($settings->get('billing', 'iyzico.enabled', false), FILTER_VALIDATE_BOOLEAN),
            'sandbox' => filter_var($settings->get('billing', 'iyzico.sandbox', true), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    private function loadDunningSettings(SettingsRepository $settings): array
    {
        return [
            'grace_days' => (int) $settings->get('billing', 'dunning.grace_days', 3),
            'send_reminders' => filter_var($settings->get('billing', 'dunning.send_reminders', true), FILTER_VALIDATE_BOOLEAN),
            'reminder_day_1' => (int) $settings->get('billing', 'dunning.reminder_day_1', 0),
            'reminder_day_2' => (int) $settings->get('billing', 'dunning.reminder_day_2', 2),
            'auto_downgrade' => filter_var($settings->get('billing', 'dunning.auto_downgrade', true), FILTER_VALIDATE_BOOLEAN),
        ];
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function loadFeatureMatrix(SettingsRepository $settings): array
    {
        $raw = $settings->get('features', 'plan_matrix', null);
        $decoded = null;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
        } elseif (is_array($raw)) {
            $decoded = $raw;
        }

        if (!is_array($decoded)) {
            $decoded = config('features.plan_matrix', []);
        }
$normalized = [];
        foreach ($decoded as $plan => $features) {
            if (!is_string($plan)) {
                continue;
            }
            if (!is_array($features)) {
                $features = [];
            }
$normalized[strtolower($plan)] = array_values(array_filter($features, fn ($value) => is_string($value) && $value !== ''));
        }

        return $normalized;
    }

    /**
     * @param array<string,array<int,string>> $featureMatrix
     * @return array<int,array{code:string,label:string}>
     */
    private function loadFeaturePlans(array $featureMatrix): array
    {
        $plans = Plan::query()->orderBy('sort_order')->get();
        if ($plans->isEmpty()) {
            $fallback = array_keys($featureMatrix);
            if ($fallback === []) {
                $fallback = array_keys(config('features.plan_matrix', []));
            }

            return array_map(fn ($code) => ['code' => (string) $code, 'label' => (string) $code], $fallback);
        }

        return $plans->map(fn (Plan $plan) => [
            'code' => strtolower((string) $plan->slug),
            'label' => $plan->name,
        ])->all();
    }

    private function isValidTimeString(string $value): bool
    {
        if (!preg_match('/^\d{2}:\d{2}$/', $value)) {
            return false;
        }
[$hour, $minute] = array_map('intval', explode(':', $value, 2));

        return $hour >= 0 && $hour <= 23 && $minute >= 0 && $minute <= 59;
    }

    private function quickActionOptions(): array
    {
        return [
            'invoices.create' => [
                'label' => 'Fatura Ekle',
                'route' => 'portal.invoices.create',
            ],
            'categories.create' => [
                'label' => 'Kategori Ekle',
                'route' => 'portal.categories.create',
            ],
            'brands.create' => [
                'label' => 'Marka Ekle',
                'route' => 'portal.brands.create',
            ],
            'products.create' => [
                'label' => 'Ürün Ekle',
                'route' => 'portal.products.create',
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





