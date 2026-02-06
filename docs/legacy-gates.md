Legacy gating scan report
Generated: $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")

Patterns:
- plan.module:
- plan.marketplace
- EnsurePlanModule
- EnsurePlanMarketplace
- hasPlanModule(
- $hasModule('reports.
- $hasModule('exports.
- features.plan_modules
- custom_integrations
- enabledModules(

Matches:.\docs\SESSION.md:77:- Super-admin Plan create/edit: modül whitelist yönetimi eklendi (Module model’den feature/integration listesi). Plan içi yetkiler ayrıştırıldı: `features.modules` (entitlements) + `features.plan_modules` (plan.module / marketplace izinleri).
.\docs\SESSION.md:88:0c) Plan modül yönetimi: super-admin plan edit’ten whitelist’i düzenle, sonra müşteri hesabında `plan.module:*` ve `module:*` gate’leri doğrula.
.\docs\SESSION.md:116:- Mevcut paket bazlı gating: `app/Models/Plan.php` (`hasModule`) + `app/Http/Middleware/EnsurePlanModule.php` / `EnsurePlanMarketplace.php` ve `bootstrap/app.php` alias `plan.module`.
.\tests\Unit\PlanHasModuleTest.php:14:        $this->assertSame([], $plan->enabledModules());
.\tests\Unit\PlanHasModuleTest.php:26:        $this->assertSame([], $plan->enabledModules());
.\tests\Unit\PlanHasModuleTest.php:52:        $this->assertContains('reports', $plan->enabledModules());
.\docs\legacy-gates.md:5:- plan.module:
.\docs\legacy-gates.md:6:- plan.marketplace
.\docs\legacy-gates.md:7:- EnsurePlanModule
.\docs\legacy-gates.md:8:- EnsurePlanMarketplace
.\docs\legacy-gates.md:9:- hasPlanModule(
.\docs\legacy-gates.md:10:- $hasModule('reports.
.\docs\legacy-gates.md:11:- $hasModule('exports.
.\docs\legacy-gates.md:12:- features.plan_modules
.\docs\legacy-gates.md:13:- custom_integrations
.\docs\legacy-gates.md:14:- enabledModules(
.\docs\legacy-gates.md:16:Matches:./bootstrap/app.php:22:            'plan.module' => \App\Http\Middleware\EnsurePlanModule::class,
.\docs\legacy-gates.md:17:./bootstrap/app.php:23:            'plan.marketplace' => \App\Http\Middleware\EnsurePlanMarketplace::class,
.\docs\legacy-gates.md:18:./docs/SESSION.md:77:- Super-admin Plan create/edit: modül whitelist yönetimi eklendi (Module model’den feature/integration listesi). Plan içi yetkiler ayrıştırıldı: `features.modules` (entitlements) + `features.plan_modules` (plan.module / marketplace izinleri).
.\docs\legacy-gates.md:19:./docs/SESSION.md:88:0c) Plan modül yönetimi: super-admin plan edit’ten whitelist’i düzenle, sonra müşteri hesabında `plan.module:*` ve `module:*` gate’leri doğrula.
.\docs\legacy-gates.md:20:./docs/SESSION.md:116:- Mevcut paket bazlı gating: `app/Models/Plan.php` (`hasModule`) + `app/Http/Middleware/EnsurePlanModule.php` / `EnsurePlanMarketplace.php` ve `bootstrap/app.php` alias `plan.module`.
.\docs\legacy-gates.md:21:./tests/Unit/PlanHasModuleTest.php:14:        $this->assertSame([], $plan->enabledModules());
.\docs\legacy-gates.md:22:./tests/Unit/PlanHasModuleTest.php:26:        $this->assertSame([], $plan->enabledModules());
.\docs\legacy-gates.md:23:./tests/Unit/PlanHasModuleTest.php:52:        $this->assertTrue($plan->hasPlanModule('reports.orders'));
.\docs\legacy-gates.md:24:./tests/Unit/PlanHasModuleTest.php:53:        $this->assertTrue($plan->hasPlanModule('reports'));
.\docs\legacy-gates.md:25:./tests/Unit/PlanHasModuleTest.php:54:        $this->assertFalse($plan->hasPlanModule('feature.api_access'));
.\docs\legacy-gates.md:26:./database/seeders/PlanSeeder.php:33:                'custom_integrations' => false,
.\docs\legacy-gates.md:27:./database/seeders/PlanSeeder.php:63:                'custom_integrations' => false,
.\docs\legacy-gates.md:28:./database/seeders/PlanSeeder.php:98:                'custom_integrations' => true,
.\docs\legacy-gates.md:29:./routes/customer.php:52:            ->middleware('plan.module:exports.invoices')
.\docs\legacy-gates.md:30:./routes/customer.php:88:        Route::middleware('plan.module:category_mapping')->group(function () {
.\docs\legacy-gates.md:31:./routes/customer.php:102:            ->middleware('plan.module:exports.products')
.\docs\legacy-gates.md:32:./routes/customer.php:110:            ->middleware('plan.module:exports.orders')
.\docs\legacy-gates.md:35:./routes/customer.php:155:                    ->middleware('plan.module:exports.reports.top_products')
.\docs\legacy-gates.md:36:./routes/customer.php:158:                    ->middleware('plan.module:exports.reports.orders')
.\docs\legacy-gates.md:37:./routes/customer.php:161:                    ->middleware('plan.module:exports.reports.orders')
.\docs\legacy-gates.md:38:./routes/customer.php:172:        Route::middleware('plan.module:integrations')->group(function () {
.\docs\legacy-gates.md:39:./routes/customer.php:174:            Route::get('integrations/{marketplace}', [IntegrationController::class, 'edit'])->middleware('plan.marketplace')->name('integrations.edit');
.\docs\legacy-gates.md:40:./routes/customer.php:175:            Route::put('integrations/{marketplace}', [IntegrationController::class, 'update'])->middleware('plan.marketplace')->name('integrations.update');
.\docs\legacy-gates.md:41:./routes/customer.php:176:            Route::post('integrations/{marketplace}', [IntegrationController::class, 'test'])->middleware('plan.marketplace')->name('integrations.test');
.\docs\legacy-gates.md:42:./routes/customer.php:179:        Route::resource('sub-users', AdminSubUserController::class)->middleware('plan.module:sub_users')->except(['show']);
.\docs\legacy-gates.md:43:./routes/customer.php:194:        Route::middleware('plan.module:tickets')->group(function () {
.\docs\legacy-gates.md:45:./app/Http/Middleware/EnsurePlanModule.php:9:class EnsurePlanModule
.\docs\legacy-gates.md:46:./app/Http/Middleware/EnsurePlanModule.php:38:        if ($plan->hasPlanModule($moduleKey)) {
.\docs\legacy-gates.md:47:./tests/Feature/Webhooks/WebhookServiceDispatchTest.php:49:            'custom_integrations' => false,
.\docs\legacy-gates.md:48:./app/Http/Middleware/EnsurePlanMarketplace.php:9:class EnsurePlanMarketplace
.\docs\legacy-gates.md:49:./app/Http/Middleware/EnsurePlanMarketplace.php:44:        if ($plan->hasPlanModule('integrations.marketplace.' . $code)) {
.\docs\legacy-gates.md:50:./tests/Feature/Settings/WebhooksUpsellTest.php:44:            'custom_integrations' => false,
.\docs\legacy-gates.md:51:./tests/Feature/Settings/ApiTokenCreateSecurityFieldsTest.php:36:            'custom_integrations' => false,
.\docs\legacy-gates.md:52:./tests/Feature/Settings/ApiAccessUpsellTest.php:35:            'custom_integrations' => false,
.\docs\legacy-gates.md:53:./tests/Feature/Settings/ApiAccessDocsLinkTest.php:35:            'custom_integrations' => false,
.\docs\legacy-gates.md:54:./tests/Feature/EInvoices/EInvoiceServiceTest.php:168:            'custom_integrations' => false,
.\docs\legacy-gates.md:55:./tests/Feature/EInvoices/EInvoiceProviderTest.php:38:            'custom_integrations' => false,
.\docs\legacy-gates.md:56:./tests/Feature/EInvoices/EInvoiceProviderTest.php:127:            'custom_integrations' => false,
.\docs\legacy-gates.md:57:./app/Http/Controllers/SuperAdmin/PlanController.php:159:            'custom_integrations' => 'boolean',
.\docs\legacy-gates.md:58:./app/Http/Controllers/SuperAdmin/PlanController.php:173:        $validated['custom_integrations'] = $request->boolean('custom_integrations');
.\docs\legacy-gates.md:59:./app/Http/Controllers/SuperAdmin/PlanController.php:202:        $selectedModules = $plan->enabledModules();
.\docs\legacy-gates.md:60:./app/Http/Controllers/SuperAdmin/PlanController.php:225:            'custom_integrations' => 'boolean',
.\docs\legacy-gates.md:61:./app/Http/Controllers/SuperAdmin/PlanController.php:239:        $validated['custom_integrations'] = $request->boolean('custom_integrations');
.\docs\legacy-gates.md:63:./tests/Feature/Api/EInvoiceApiTest.php:36:            'custom_integrations' => false,
.\docs\legacy-gates.md:64:./tests/Feature/Api/ApiTokenSecurityTest.php:35:            'custom_integrations' => false,
.\docs\legacy-gates.md:65:./app/Models/Plan.php:52:        'custom_integrations',
.\docs\legacy-gates.md:66:./app/Models/Plan.php:63:        'custom_integrations' => 'boolean',
.\docs\legacy-gates.md:67:./app/Models/Plan.php:96:    public function enabledModules(): array
.\docs\legacy-gates.md:68:./app/Models/Plan.php:124:        $modules = $this->enabledModules();
.\docs\legacy-gates.md:69:./app/Models/Plan.php:177:    public function hasPlanModule(string $moduleKey): bool
.\docs\legacy-gates.md:71:./database/migrations/2026_01_23_060033_create_plans_table.php:26:            $table->boolean('custom_integrations')->default(false);
.\docs\legacy-gates.md:74:./app/Http/Controllers/Admin/IntegrationController.php:32:                ->filter(fn ($marketplace) => $plan->hasPlanModule('integrations.marketplace.' . ($marketplace->code ?? '')))
.\docs\legacy-gates.md:76:./resources/views/super-admin/plans/edit.blade.php:80:                    <input type="checkbox" name="custom_integrations" value="1" class="rounded" @checked(old('custom_integrations', $plan->custom_integrations))>
.\docs\legacy-gates.md:85:./resources/views/super-admin/plans/create.blade.php:79:                    <input type="checkbox" name="custom_integrations" value="1" class="rounded" @checked(old('custom_integrations'))>
.\database\seeders\PlanSeeder.php:36:                'custom_integrations' => false,
.\database\seeders\PlanSeeder.php:65:                'custom_integrations' => false,
.\database\seeders\PlanSeeder.php:105:                'custom_integrations' => true,
.\bootstrap\app.php:22:            'plan.module' => \App\Http\Middleware\EnsurePlanModule::class,
.\bootstrap\app.php:23:            'plan.marketplace' => \App\Http\Middleware\EnsurePlanMarketplace::class,
.\tests\Feature\Webhooks\WebhookServiceDispatchTest.php:49:            'custom_integrations' => false,
.\tests\Feature\Settings\WebhooksUpsellTest.php:44:            'custom_integrations' => false,
.\tests\Feature\Settings\ApiTokenCreateSecurityFieldsTest.php:36:            'custom_integrations' => false,
.\tests\Feature\Settings\ApiAccessUpsellTest.php:35:            'custom_integrations' => false,
.\tests\Feature\Settings\ApiAccessDocsLinkTest.php:35:            'custom_integrations' => false,
.\resources\views\super-admin\plans\edit.blade.php:80:                    <input type="checkbox" name="custom_integrations" value="1" class="rounded" @checked(old('custom_integrations', $plan->custom_integrations))>
.\resources\views\super-admin\plans\create.blade.php:79:                    <input type="checkbox" name="custom_integrations" value="1" class="rounded" @checked(old('custom_integrations'))>
.\app\Http\Middleware\EnsurePlanModule.php:9:class EnsurePlanModule
.\app\Http\Middleware\EnsurePlanMarketplace.php:9:class EnsurePlanMarketplace
.\tests\Feature\EInvoices\EInvoiceServiceTest.php:168:            'custom_integrations' => false,
.\tests\Feature\EInvoices\EInvoiceProviderTest.php:38:            'custom_integrations' => false,
.\tests\Feature\EInvoices\EInvoiceProviderTest.php:127:            'custom_integrations' => false,
.\app\Models\Plan.php:52:        'custom_integrations',
.\app\Models\Plan.php:63:        'custom_integrations' => 'boolean',
.\app\Models\Plan.php:96:    public function enabledModules(): array
.\app\Models\Plan.php:130:        $modules = $this->enabledModules();
.\tests\Feature\Api\EInvoiceApiTest.php:36:            'custom_integrations' => false,
.\tests\Feature\Api\ApiTokenSecurityTest.php:35:            'custom_integrations' => false,
.\app\Http\Controllers\SuperAdmin\PlanController.php:110:            'custom_integrations' => 'boolean',
.\app\Http\Controllers\SuperAdmin\PlanController.php:122:        $validated['custom_integrations'] = $request->boolean('custom_integrations');
.\app\Http\Controllers\SuperAdmin\PlanController.php:140:        $selectedModules = $plan->enabledModules();
.\app\Http\Controllers\SuperAdmin\PlanController.php:162:            'custom_integrations' => 'boolean',
.\app\Http\Controllers\SuperAdmin\PlanController.php:174:        $validated['custom_integrations'] = $request->boolean('custom_integrations');
.\database\migrations\2026_01_23_060033_create_plans_table.php:26:            $table->boolean('custom_integrations')->default(false);

