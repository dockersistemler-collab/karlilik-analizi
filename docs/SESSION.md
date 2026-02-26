# Session Memory

**Last updated:** 2026-02-26

## Current Work (2026-02-26 - Control Tower v1)
- Goal: Add Marketplace Intelligence Control Tower (CFO + OPS) as a gated top module with snapshot + signal engine.
- Status: Implemented end-to-end (migration, services, cache, job, schedule, routes, controller, UI, tests).

## Changes Made (2026-02-26 - Control Tower v1)
- Added module data layer:
  - `database/migrations/2026_02_26_160000_create_control_tower_tables.php`
  - `app/Models/ControlTowerDailySnapshot.php`
  - `app/Models/ControlTowerSignal.php`
- Added control tower service layer:
  - `app/Services/ControlTower/ControlTowerDataSources.php`
  - `app/Services/ControlTower/ControlTowerAggregator.php`
  - `app/Services/ControlTower/SignalEngine.php`
  - `app/Services/ControlTower/ControlTowerCache.php`
- Added snapshot build job:
  - `app/Jobs/BuildControlTowerDailySnapshotJob.php`
  - writes daily payload snapshot, upserts signals, emits critical control tower notifications with dedupe key.
- Added scheduler:
  - `routes/console.php` daily `04:00` (`control_tower_daily_snapshot`) for active tenants with enabled `feature.control_tower`.
- Added admin routes/controller:
  - `routes/customer.php` -> `portal.control-tower.*` under `/admin/control-tower` with `module:feature.control_tower,404`.
  - `app/Http/Controllers/Admin/ControlTowerController.php`:
    - `index`, `signals`, `resolveSignal`
    - drilldowns: `profit-leak`, `buybox`, `risk`, `campaigns`, `actions`
- Added UI pages/components:
  - `resources/views/admin/control-tower/index.blade.php`
  - `resources/views/admin/control-tower/signals.blade.php`
  - `resources/views/admin/control-tower/drilldown.blade.php`
  - components:
    - `ct-kpi-card.blade.php`
    - `ct-alert-card.blade.php`
    - `ct-signal-list.blade.php` (Why modal + Run Action + Resolve)
    - `ct-mini-trend.blade.php` (SVG sparkline)
- Added module/menu/permission wiring:
  - `database/seeders/ModuleCatalogSeeder.php`: `feature.control_tower`
  - `database/seeders/PlanSeeder.php`: module included in Starter/Professional/Enterprise.
  - `app/Http/Middleware/EnsureSubUserPermission.php`: `portal.control-tower.* -> reports.control_tower`
  - `app/Http/Controllers/Admin/SubUserController.php`: added label `reports.control_tower`
  - `resources/views/layouts/admin.blade.php`: menu links:
    - `Control Tower (CFO)`
    - `Control Tower (Operasyon)`
- Added tests:
  - `tests/Feature/ControlTower/ControlTowerModuleTest.php`
  - `tests/Unit/ControlTower/SignalEngineTest.php`

## Tests/Validation (2026-02-26 - Control Tower v1)
- `php artisan test --filter="ControlTowerModuleTest|SignalEngineTest"` => PASS (`5 passed`).
- `php -l` checks passed for new/edited controller, services, routes, seeders, middleware.

## Next Session Start Note (2026-02-26 - Control Tower v1)
1) Run migration + seed in dev env to expose module in real UI:
   - `php artisan migrate`
   - `php artisan db:seed --class=ModuleCatalogSeeder`
   - `php artisan db:seed --class=PlanSeeder`
2) Manual smoke:
   - `/admin/control-tower?view=cfo`
   - `/admin/control-tower?view=ops`
3) Optional v1.1:
   - richer cashflow model via payout schedule,
   - stronger ALGO_SHIFT detector with traffic/order proxy source.

## Current Work (2026-02-26 - BuyBox FAZ 4 UI Devam)
- Goal: Add explicit impact simulation summary cards for BuyBox pages (scores + SKU detail).
- Status: Implemented in controller/views; syntax check passed.

## Changes Made (2026-02-26 - BuyBox FAZ 4 UI Devam)
- Added BuyBox impact summary aggregation in:
  - `app/Http/Controllers/Admin/BuyBoxController.php`
  - New helper: `buildBuyBoxImpactSummary(...)`
  - Scope: BuyBox action types (`PRICE_ADJUST`, `SHIPPING_SLA_FIX`, `STOCK_FIX`, `LISTING_OPTIMIZE`)
  - Metrics: recommendation/open count, avg win-probability delta (pp), sum/avg net-profit delta, avg confidence.
- Updated BuyBox pages:
  - `resources/views/admin/buybox/scores.blade.php`
    - Added FAZ 4 summary cards (impact count, win delta, net profit delta, confidence).
  - `resources/views/admin/buybox/detail.blade.php`
    - Added SKU-level FAZ 4 summary cards.
    - Added per-recommendation columns: `Win Delta`, `Net Kar Delta`.

## Tests/Validation (2026-02-26 - BuyBox FAZ 4 UI Devam)
- `php -l app/Http/Controllers/Admin/BuyBoxController.php` => PASS.

## Next Session Start Note (2026-02-26 - BuyBox FAZ 4 UI Devam)
1) Run targeted feature tests for BuyBox/Action Engine views if needed.
2) Optional: add formatting unit (currency symbol / locale) for net-profit delta cards.
3) Optional: expose same FAZ 4 cards on snapshots page (`admin.buybox.index`) if requested.

## Current Work (2026-02-26)
- Goal: Complete BuyBox phases (FAZ 1-2), integrate Action Engine BuyBox rules (FAZ 3), and fix module visibility/naming on client + super-admin.
- Status: Implemented and validated with targeted tests passing.

## Changes Made (2026-02-26)
- BuyBox FAZ 1 implemented:
  - snapshot/competitor tables, models, adapters, collection job, admin BuyBox snapshot UI, CSV import/export, routes, module/plan wiring.
- BuyBox FAZ 2 implemented:
  - score/profile tables, calculator service, score job, scores/profiles UI, routes, tests.
- BuyBox FAZ 3 implemented:
  - Action Engine BuyBox rules:
    - `PRICE_ADJUST` (min margin guard)
    - `SHIPPING_SLA_FIX` (store_score/shipping_speed driver)
    - `STOCK_FIX` (low stock)
    - risk `critical` blocks `PRICE_ADJUST` and emits `LISTING_OPTIMIZE` note.
  - Scoped recommendation run from BuyBox UI (`Aksiyon Oner`).
  - BuyBox SKU detail page with score/store/price-gap trends and recommendation list.
  - Impact simulator extended for `PRICE_ADJUST`.
- Visibility/name fixes:
  - Client sidebar labels updated:
    - `BuyBox Engine (FAZ 1-2)`
    - `Action Engine (FAZ 3-4)`
  - Super-admin intelligence updated:
    - new route/page: `super-admin.intelligence.buybox-engine`
    - new sidebar entry: `BuyBox Engine (FAZ 1-2)`
    - label: `Action Engine (FAZ 3-4)`
  - Super-admin module toggle now supports `buybox_engine`.
- Cache refresh:
  - `php artisan view:clear`
  - `php artisan route:clear`

## Tests/Validation (2026-02-26)
- `php artisan test --filter="BuyBoxActionRulesTest|ActionEngineWorkflowTest|BuyBoxEngineTest|BuyBoxScoreCalculatorTest"` => PASS (`12 passed`).
- `php artisan route:list --name=super-admin.intelligence` => includes `super-admin.intelligence.buybox-engine`.

## Next Session Start Note (2026-02-26)
- Continue from BuyBox/Action integration.
- Optional next step:
  1) Add FAZ 4 explicit impact simulation UI cards for BuyBox recommendations (win probability delta + profit delta summary on BuyBox pages).

## Current Work (2026-02-25)
- Goal: Build Action Engine Phase 4 (impact simulation, calibration, shock detection, campaign import/calendar application).
- Status: Core implementation completed with targeted tests passing.

## Changes Made (2026-02-25)
- Added Phase 4 schema:
  - `action_recommendation_impacts`
  - `marketplace_price_history`
  - `action_engine_calibrations`
  - `marketplace_external_shocks`
  - `marketplace_campaigns`, `marketplace_campaign_items`
- Added models:
  - `ActionRecommendationImpact`
  - `MarketplacePriceHistory`
  - `ActionEngineCalibration`
  - `MarketplaceExternalShock`
  - `MarketplaceCampaign`, `MarketplaceCampaignItem`
- Added services:
  - `ImpactSimulator`
  - `PriceHistoryBuilder`
  - `ShockDetector`
  - `CampaignCsvImporter`
  - `CampaignCalendarApplier`
  - `CalibrationEngine`
- Added jobs:
  - `DetectMarketplaceShocksJob`
  - `RunActionEngineCalibrationJob`
- Integrated ActionEngine:
  - New recommendation creation now triggers impact simulation auto-run.
  - Recommendation detail page includes Expected Impact card + refresh endpoint.
- Added admin UI/flows:
  - `/admin/action-engine/calibration`
  - `/admin/action-engine/shocks`
  - `/admin/action-engine/campaigns` (CSV import + apply)
  - manual trigger endpoints for shock detect/calibration
- Added schedules:
  - `03:10` daily => detect marketplace shocks (45-day window)
  - `03:20` daily => run action engine calibration
- Validation:
  - `php artisan test --filter="ActionEnginePhase4Test|ActionEngineWorkflowTest"` => PASS
  - `php artisan test --filter="CalculateOrderProfitJobTest|MarketplaceRiskEngineTest|ActionEngineWorkflowTest|ActionEnginePhase4Test"` => PASS

## Current Work (2026-02-25)
- Goal: Build Action Engine Phase 3 (risk + net profitability fusion, actionable recommendations, lifecycle, notifications, admin console).
- Status: Core implementation completed and targeted tests passing.

## Changes Made (2026-02-25)
- Added Action Engine schema:
  - `action_recommendations`
  - `action_engine_runs`
- Added Action Engine services + job:
  - `app/Services/ActionEngine/ActionEngine.php`
  - `app/Services/ActionEngine/RecommendationWriter.php`
  - `app/Services/ActionEngine/NotificationPublisher.php`
  - `app/Jobs/RunActionEngineDailyJob.php`
- Rule outputs implemented:
  - `CRITICAL risk + negative net_profit` => `PRICE_INCREASE` or `LISTING_SUSPEND`
  - `late_shipment_rate` driver => `SHIPPING_SLA_FIX`
  - `return_rate` driver + low margin => `RULE_REVIEW` / `CUSTOMER_SUPPORT`
  - `amazon + odr` driver => `CUSTOMER_SUPPORT`
- Lifecycle + dedupe:
  - Recommendation status: `open / applied / dismissed`
  - Dedupe key dimensions: `tenant + date + action_type + marketplace + sku`
  - Applied/dismissed recommendations are not reopened by writer.
- Notification:
  - New `open` recommendation creates in-app notification (`source=action_engine`).
- Added admin module:
  - Controller: `app/Http/Controllers/Admin/ActionEngineController.php`
  - UI:
    - `resources/views/admin/action-engine/index.blade.php`
    - `resources/views/admin/action-engine/show.blade.php`
  - Routes under `/admin/action-engine` with `module:action_engine`
  - Actions: list/filter, detail, apply, dismiss, manual run by date range.
- Added schedule:
  - `routes/console.php` daily `03:00` action engine run for yesterday with module gate check.
- Added module/permission wiring:
  - `action_engine` in `ModuleCatalogSeeder` and plan module lists.
  - sub-user permission mapping `reports.action_engine`.
  - sidebar link in `layouts/admin.blade.php`.
- Added tests:
  - `tests/Feature/ActionEngine/ActionEngineWorkflowTest.php`
    - critical risk + negative profit => recommendation
    - dedupe behavior
    - apply/dismiss lifecycle
- Validation:
  - `php artisan test --filter="ActionEngineWorkflowTest"` => PASS
  - `php artisan route:list --name=portal.action-engine` => 5 routes listed
  - `php artisan test --filter="CalculateOrderProfitJobTest|MarketplaceRiskEngineTest|ActionEngineWorkflowTest"` => PASS

## Current Work (2026-02-25)
- Goal: Build Marketplace Risk Phase 2 (KPI snapshots, risk scoring, drivers/trends, warning-critical notifications, admin management UI).
- Status: Core implementation completed with targeted tests passing.

## Changes Made (2026-02-25)
- Added Marketplace Risk schema:
  - `marketplace_kpi_snapshots`
  - `marketplace_risk_profiles`
  - `marketplace_risk_scores`
- Added Marketplace Risk domain/service/job:
  - `app/Models/MarketplaceKpiSnapshot.php`
  - `app/Models/MarketplaceRiskProfile.php`
  - `app/Models/MarketplaceRiskScore.php`
  - `app/Services/MarketplaceRisk/ProfileResolver.php`
  - `app/Services/MarketplaceRisk/RiskCalculator.php`
  - `app/Services/MarketplaceRisk/NotificationPublisher.php`
  - `app/Jobs/CalculateMarketplaceRiskJob.php`
- Added admin module:
  - routes: `portal.marketplace-risk.*` under `/admin/marketplace-risk` with `module:marketplace_risk`
  - controller: `app/Http/Controllers/Admin/MarketplaceRiskController.php`
  - UI: `resources/views/admin/marketplace-risk/index.blade.php`
  - features:
    - KPI manual create/update
    - KPI CSV import
    - risk profile CRUD
    - risk score overview and alert list
- Added schedule:
  - `routes/console.php` daily `02:15` job dispatch for yesterday (`marketplace_risk_daily`) with module gate check.
- Added module/permission/sidebar wiring:
  - `marketplace_risk` in `ModuleCatalogSeeder` and plan module lists
  - sub-user permission map + label (`reports.marketplace_risk`)
  - sidebar link in `layouts/admin.blade.php`
- Added default profile seeder:
  - `database/seeders/MarketplaceRiskDefaultsSeeder.php`
  - included in `DatabaseSeeder`
- Validation:
  - `php artisan test --filter="MarketplaceRiskEngineTest"` => PASS
  - `php artisan route:list --name=portal.marketplace-risk` => 7 routes listed

## Current Work (2026-02-25)
- Goal: Build Profit Engine Phase 1 (order-level net profitability snapshots + auto calculation + admin CRUD/list screens).
- Status: Core backend/service/job/UI wiring completed and targeted tests passing.

## Changes Made (2026-02-25)
- Added Profit Engine schema:
  - `profit_cost_profiles`
  - `marketplace_fee_rules`
  - `order_profit_snapshots`
- Added models/services/job:
  - `app/Models/ProfitCostProfile.php`
  - `app/Models/MarketplaceFeeRule.php`
  - `app/Models/OrderProfitSnapshot.php`
  - `app/Services/ProfitEngine/FeeRuleResolver.php`
  - `app/Services/ProfitEngine/ProfitCalculator.php`
  - `app/Jobs/CalculateOrderProfitJob.php`
- Added auto-dispatch integration points:
  - `app/Observers/OrderObserver.php` (on order create)
  - `app/Domains/Marketplaces/Mappers/MarketplacePayloadMapper.php` (on upserted imports)
  - Dispatch guarded by module key: `profit_engine`.
- Added admin module screens/routes:
  - `routes/customer.php` under `/admin/profit-engine` (`portal.profit-engine.*`)
  - `app/Http/Controllers/Admin/ProfitEngineController.php`
  - `resources/views/admin/profit-engine/index.blade.php`
  - `resources/views/admin/profit-engine/show.blade.php`
  - Sidebar link in `resources/views/layouts/admin.blade.php`
- Added module/entitlement seed wiring:
  - `profit_engine` added to `ModuleCatalogSeeder` and plan module lists.
  - default profile + default fee rules seeder: `database/seeders/ProfitEngineDefaultsSeeder.php`
- Added tests:
  - `tests/Feature/ProfitEngine/CalculateOrderProfitJobTest.php`
- Validation:
  - `php artisan test --filter="CalculateOrderProfitJobTest"` => PASS
  - `php artisan test --filter="TrendyolOrderSyncTest"` => PASS

## Current Work (2026-02-25)
- Goal: Continue Loss Finder v11 implementation and verify new services + API workflow are stable.
- Status: Targeted v11 tests passing on current working tree; no failing tests detected in covered scope.

## Changes Made (2026-02-25)
- Validated v11 service layer and workflow tests:
  - `php artisan test --filter="LossFinderWorkflowTest|LossFinderV11ServicesTest"` => PASS
  - Covered:
    - confidence scoring
    - tenant rule resolution
    - loss pattern aggregation
    - regression guard checks
    - module gate, import/reconcile queue flow
    - export, bulk dispute actions, status updates, evidence/regression endpoints

## Next Steps (2026-02-25)
1) Manual API smoke on Loss Finder endpoints with realistic payload sizes (import -> reconcile -> findings -> disputes).
2) Replace current export placeholder with true XLSX generation if strict XLSX output is required.
3) Run broader settlement regression tests before merge.

## Current Work (2026-02-22)
- Goal: Build "Hakediş Kontrol Merkezi" backend foundation (tenant-scoped API + sync + expected payout + reconciliation + disputes).
- Status: Core module scaffold completed and targeted tests passing.

## Changes Made (2026-02-22)
- Added migrations for:
  - `tenants`, `marketplace_integrations`, `feature_flags`, `settlement_rules`
  - `order_items`, `returns`, `payouts`, `payout_transactions`, `reconciliations`, `disputes`, `sync_jobs`, `sync_logs`
  - Expanded `users`, `orders`, `marketplace_accounts` with settlement/tenant fields.
- Added tenant context stack:
  - `app/Domains/Tenancy/TenantContext.php`
  - `app/Domains/Tenancy/Concerns/BelongsToTenant.php`
  - `app/Http/Middleware/ResolveTenantContext.php`
  - middleware alias: `tenant.resolve`.
- Added domain layers:
  - mock marketplace connectors (Trendyol/HB/N11/Amazon),
  - payload mapper + sync log service + sync job,
  - rule evaluator (Net KDV + Kar formulas),
  - actions: `BuildExpectedPayoutsAction`, `ReconcilePayoutsAction`, `DetectAnomaliesAction`.
- Added API endpoints under `/api/v1`:
  - `auth/login`, tenants/features (super admin), users/roles, marketplace accounts, settlement rules,
    sync trigger, payouts (+transactions/reconcile/export), disputes, dashboard.
- Added seed/factory/test artifacts:
  - `HakedisKontrolMerkeziSeeder`, `TenantFactory`, `MarketplaceAccountFactory`
  - tests:
    - `tests/Unit/SettlementRuleEvaluatorTest.php`
    - `tests/Feature/Api/TenantIsolationPayoutsTest.php`
    - `tests/Feature/Api/BuildExpectedPayoutsActionTest.php`
    - `tests/Feature/Api/ReconcileCreatesDisputeTest.php`
- Validation:
  - `php artisan test --filter="SettlementRuleEvaluatorTest|TenantIsolationPayoutsTest|BuildExpectedPayoutsActionTest|ReconcileCreatesDisputeTest"` => PASS.

## Next Steps (2026-02-22)
1) Replace mock connectors with real marketplace HTTP clients + retry/backoff + pagination cursors.
2) Wire `spatie/laravel-permission` for persisted RBAC (current roles endpoint is fallback).
3) Add structured activity/audit logging for payout/dispute state transitions.
4) Expand reconciliation heuristics (reference clusters/date+amount scoring/manual override).
5) Add XLSX export and richer settlement dashboard aggregations.

## Current Work (2026-02-21)
- Goal: Continue prior session by validating pending billing/commission targets and fixing newly detected mojibake.
- Status: Target tests passing; mojibake cleanup completed on affected files; text quality scan clean.

## Changes Made (2026-02-21)
- Ran targeted continuation tests:
  - `php artisan test --filter=TRNumberParserTest` => PASS
  - `php artisan test --filter=CommissionTariffMatcherTest` => PASS
  - `php artisan test --filter=CommissionTariffProfitCalculatorTest` => PASS
  - `php artisan test --filter=PortalPastDueCtaTest` => PASS
  - `php artisan test --filter=PortalActiveNoWarningTest` => PASS
- Ran text quality scan and fixed reported mojibake in:
  - `resources/views/admin/orders/index.blade.php`
  - `app/Http/Controllers/Admin/ProductController.php`
- Normalized changed files to UTF-8 without BOM (to avoid namespace/BOM runtime issues in PHP files).
- Validation:
  - `powershell -ExecutionPolicy Bypass -File scripts/check-ui-text-quality.ps1 -Root .` => PASS
  - `php -l app/Http/Controllers/Admin/ProductController.php` => PASS

## Next Steps (2026-02-21)
1) Manual smoke check on `/portal/billing` past-due copy/CTA state.
2) Functional QA pass for `/commission-tariffs` flow (upload -> map -> import -> recalc -> export).
3) If needed, run broader billing/commission feature tests after manual QA.

## Current Work (2026-02-20)
- Goal: Continue from prior session by executing queued deliverability/integration tests and re-check UI text quality.
- Status: Target tests passing; one public layout mojibake hotspot fixed; text quality scan clean.

## Changes Made (2026-02-20)
- Ran targeted tests from previous next-steps list:
  - `php artisan test --filter=NotificationHubDeliverabilityTest` => PASS
  - `php artisan test --filter=EmailSuppressionTest` => PASS
  - `php artisan test --filter=IntegrationHealthTest` => PASS
- Ran `scripts/check-ui-text-quality.ps1` and fixed reported text encoding issues in:
  - `resources/views/layouts/public.blade.php`
    - `Fiyatland?rma` -> `Fiyatlandırma`
    - `Kay?t Ol` -> `Kayıt Ol`
    - `Giri?` -> `Giriş` (all occurrences on this layout)
    - Footer copy normalized with `&copy;` and proper Turkish characters.
- Validation:
  - `powershell -ExecutionPolicy Bypass -File scripts/check-ui-text-quality.ps1 -Root .` => PASS.

## Next Steps (2026-02-20)
1) Manual smoke check on `/portal/billing` past-due copy/CTA state.
2) Functional QA pass for `/commission-tariffs` flow (upload -> map -> import -> recalc -> export).
3) If needed, run targeted commission tariff tests (`TRNumberParser`, matcher, profitability calculator) after UI QA.

## Current Work (2026-02-18)
- Goal: Continue from prior session by cleaning remaining UI mojibake text.
- Status: Text quality scan clean; no issue detected.

## Changes Made (2026-02-18)
- Ran `scripts/check-ui-text-quality.ps1` and fixed all reported issues.
- Updated files:
  - `resources/views/admin/orders/index.blade.php` (`Görsel`)
  - `resources/views/admin/orders/show.blade.php` (`Başlangıç`)
  - `resources/views/admin/products/partials/catalog-tabs.blade.php` (multiple mojibake fixes)
- Validation:
  - `powershell -ExecutionPolicy Bypass -File scripts/check-ui-text-quality.ps1 -Root .` => PASS.

## Next Steps (2026-02-18)
1) Run manual smoke check on `/portal/billing` for past-due copy/CTA.
2) Continue functional QA for `/commission-tariffs` flow (upload -> map -> import -> recalc -> export).
3) Optionally run targeted tests for billing and commission tariff flows.
## Current Work (2026-02-17)
- Goal: Continue from prior session and stabilize test suite on current branch.
- Status: Test suite stabilized; full run passes.

## Changes Made (2026-02-17)
- Fixed sqlite compatibility drifts in migrations/tests:
  - `users.role` supports `support_agent` in fresh sqlite runs.
  - `module_purchases.provider` supports `fake` in fresh sqlite runs.
  - `support_access_logs.super_admin_id` is nullable for support-agent flows.
  - `orders.marketplace_id` is nullable in fresh schema.
- Updated/normalized failing tests:
  - API tests now seed required module records for module gating.
  - Feature gating/integration health tests seed `feature.integrations` module.
  - Profitability index assertion aligned with current page copy.
  - Past-due CTA test moved to billing page and now asserts stable CTA signals.
- Validation:
  - `php artisan test` => PASS (`266 passed`).

## Next Steps (2026-02-17)
1) If needed, run a quick manual check on billing UI (`/portal/billing`) for past-due state copy/CTA text.
2) Optionally clean remaining mojibake text in views where visible (some labels are still mixed-encoding).
3) Continue with functional QA for commission tariff UI flow (`/commission-tariffs`) as previously planned.`r`n`r`n## Current Work (2026-02-14)
- Goal: Add “Ürün Komisyon Tarifeleri” module (Excel upload + mapping + profit calc + UI).
- Status: Config, migrations, models, services, job/imports, API + export, UI page, routes, and tests added.

## Changes Made (2026-02-14)
- Added commission tariff config (`config/commission_tariffs.php`) and migrations for uploads/rows/assignments plus `product_variants`.
- Added models (`CommissionTariffUpload`, `CommissionTariffRow`, `CommissionTariffAssignment`, `ProductVariant`) and Product relations.
- Implemented import pipeline (TR number parser, preview import, chunked job, matcher, assignment service).
- Implemented profit calculator + shipping fee resolver and export.
- Added API endpoints + web page `/commission-tariffs` and admin sidebar + sub-user permissions.
- Added tests: TRNumberParser, ProfitCalculator, Matcher.

## Next Steps (2026-02-14)
1) Run migrations and install `maatwebsite/excel` (`composer install` or `composer update`).
2) Verify `/commission-tariffs` UI flows: upload -> map -> import -> list -> recalc -> export.
3) Seed or create product_variants to enable matching.

## Current Work (2026-02-14)
- Goal: Add Inventory module integrated into existing Manageable Modules system.
- Status: Inventory module + connector sub-modules added; admin/user routes and screens implemented; module-off now returns 404 on inventory routes.

## Changes Made (2026-02-14)
- Super Admin module management:
  - Added module catalog seeds:
    - `feature.inventory` (default disabled)
    - `integration.inventory.trendyol`
    - `integration.inventory.hepsiburada`
    - `integration.inventory.n11`
    - `integration.inventory.amazon`
  - Added quick toggle action to `super-admin/modules` list.
- Middleware and gating:
  - Extended `EnsureModuleEnabled` with optional fail mode (`module:code,404`).
  - Inventory routes now use 404 gating when module is disabled.
  - Added sub-user permission route mapping for inventory routes.
- Inventory data model:
  - Added `products.critical_stock_level`.
  - Added `stock_movements`, `marketplace_listings`, `stock_alerts` tables.
  - Extended existing `marketplace_accounts` with inventory-compatible columns:
    - `connector_key`, `credentials_json`, `is_active`, `last_sync_at`
  - Added models: `StockMovement`, `MarketplaceListing`, `StockAlert`.
- Inventory integration (stub):
  - Added `ProductStockUpdated` event.
  - Added `PushStockToMarketplacesJob` + listener (`QueueStockSync`).
  - Added marketplace connector contract/factory and stub connectors:
    - Trendyol, Hepsiburada, N11, Amazon.
  - Job exits if inventory module is disabled; connector-specific module toggles are respected.
- Admin/User inventory UI:
  - Admin:
    - `/admin/inventory/products` (list)
    - `/admin/inventory/products/{id}/edit` (manual increase/decrease + movement + alert + event)
    - `/admin/inventory/movements`
    - `/admin/inventory/mappings` (create/delete + sync toggle)
  - User (read-only):
    - `/user/inventory/products`
  - Sidebar now shows inventory links only when inventory module is enabled.
- Tests:
  - Added `InventoryModuleGateTest` (module-off => 404).
  - Added `InventoryStockUpdateTest` (stock update => movement + critical alert + queue job).

## Next Steps (2026-02-14)
1) Run migrations and seeders on target env:
   - `php artisan migrate`
   - `php artisan db:seed --class=ModuleCatalogSeeder`
2) Verify super-admin module toggles for inventory and connectors in UI.
3) Optionally add edit form fields for listing updates in mappings screen (currently create/delete + quick sync toggle).

## Current Work (2026-02-12)
- Goal: Stabilize local run, fix encoding regressions, and clean admin sidebar/menu.
- Status: Local app is running on port `8200`; critical PHP fatal (namespace/BOM) fixed; duplicate invoice menu item removed.

## Changes Made (2026-02-12)
- Local dev/runtime:
  - Standardized run target to port `8200`.
  - Updated `.env` `APP_URL` to `http://pazar.test:8200`.
  - Verified listener on `127.0.0.1:8200`.
- Fatal error fix:
  - Resolved `Namespace declaration statement has to be the very first statement` caused by BOM.
  - Removed UTF-8 BOM from affected PHP/Blade files (38 files), including `app/Models/Marketplace.php`.
- Encoding cleanup:
  - Fixed widespread mojibake (`Ã/Å/Ä` style) in many UI and backend strings.
  - Corrected key strings in orders/settings/sub-users/super-admin views and related controllers/tests.
- Sidebar/menu cleanup:
  - Unified invoice label to `Faturalar`.
  - Removed duplicate `Faturalar` menu entry in `resources/views/layouts/admin.blade.php`.
  - Identified route groups currently not exposed in sidebar (webhooks, incidents, notification hub, mail templates, e-invoice settings/docs, system mail logs).

## Next Steps (2026-02-12)
1) Validate remaining Turkish text across key pages in browser (`dashboard`, `orders`, `settings`, `sub-users`, `super-admin` panels).
2) Decide which hidden route groups should be added to sidebar and under which section.
3) Keep `orders` row styling untouched (per user request).

## Notes (2026-02-12)
- Active run URL: `http://app.pazar.test:8200` (and root domain on `:8200`).
- User instruction: do not modify `orders` row edge/oval styling again.

## Current Work (2026-02-06)
- Goal: Local dev run for project viewing on subdomains.
- Status: Local domain set to `pazar.test` with subdomains; server started on port `8020`.

## Changes Made
- Created `.env` from `.env.example` and generated `APP_KEY`.
- Switched `SESSION_DRIVER` to `file` for sqlite.
- Patched sqlite-incompatible migrations to no-op or alternate path:
  - `database/migrations/2026_01_24_231020_make_customers_user_id_nullable.php` (skip on sqlite)
  - `database/migrations/2026_02_02_010000_add_normalized_carrier_code_to_marketplace_carrier_mappings.php` (sqlite index path)
  - `database/migrations/2026_02_02_200000_change_cargo_credentials_to_text.php` (skip on sqlite)
  - `database/migrations/2026_02_03_000000_create_support_access_logs_table.php` (sqlite index path)
  - `database/migrations/2026_02_04_000000_make_orders_marketplace_id_nullable.php` (skip on sqlite)
  - `database/migrations/2026_02_04_000010_add_fake_provider_to_module_purchases.php` (skip on sqlite)
  - `database/migrations/2026_02_05_120010_expand_notification_audit_log_actions.php` (skip on sqlite)
  - `database/migrations/2026_02_05_150020_expand_notification_audit_log_actions_for_incidents.php` (skip on sqlite)
- Ran `php artisan migrate` to completion (sqlite).
- Updated `.env` for subdomain routing:
  - `APP_URL=http://pazar.test:8020`
  - `APP_ROOT_DOMAIN=pazar.test`
  - `APP_APP_DOMAIN=app.pazar.test`
  - `APP_SA_DOMAIN=sa.pazar.test`
  - `SESSION_DOMAIN=.pazar.test`
- Fixed super-admin login loop by aligning session cookie:
  - `SA_SESSION_COOKIE=app_session` (matches `APP_SESSION_COOKIE`)
- Cleared config cache: `php artisan config:clear`.
- Seeded data:
  - `php artisan db:seed --class=SuperAdminSeeder`
  - `php artisan db:seed --class=ModuleCatalogSeeder`
  - `php artisan db:seed --class=MarketplaceSeeder`
  - `php artisan db:seed --class=PlanSeeder`
- Verified login redirect works (no loop) and super-admin pages return 200.
- Seeded additional data:
  - `php artisan db:seed --class=MailTemplateSeeder`
  - `php artisan db:seed --class=CargoFixtureSeeder`

## Next Steps
- Ensure Windows hosts file has:
  - `127.0.0.1  pazar.test`
  - `127.0.0.1  app.pazar.test`
  - `127.0.0.1  sa.pazar.test`
- Start server if needed:
  - `php artisan serve --host=127.0.0.1 --port=8020`
- Login flow:
  - `http://pazar.test:8020/login` -> then `http://sa.pazar.test:8020/`
- If redirect loop persists, clear browser cookies for `pazar.test` and `sa.pazar.test`.

## Notes
- Host-based routes now use `pazar.test` / `app.pazar.test` / `sa.pazar.test` on port `8020`.
- Super admin credentials: `admin@pazaryeri.com` / `12345678`.

## Mail Altyapısı Özeti
- Akış: Event -> Listener (ShouldQueue) -> MailSender -> mail_logs
- Policy hook: MailPolicyService + mail_rule_assignments
- Admin panel: mail logları ve mail template yönetimi

## Tamamlanan Mail Senaryoları (M001-M012)
- M001: Support view bildirimi
  - Event: app/Events/SupportViewStarted.php
  - Listener: app/Listeners/SendSupportViewStartedMail.php
  - Template: security.support_view_used
  - Dispatch: app/Services/Admin/SupportViewService.php::start
  - Dedupe: yok
  - Test: tests/Feature/Mail/SupportViewStartedMailTest.php
- M002: Pazaryeri bağlantısı koptu
  - Event: app/Events/MarketplaceConnectionLost.php
  - Listener: app/Listeners/SendMarketplaceConnectionLostMail.php
  - Template: mp.connection_lost
  - Dispatch: app/Services/Marketplace/Category/TrendyolCategoryProvider.php::fetchCategoryTree
  - Dedupe: yok
  - Test: tests/Feature/Mail/MarketplaceConnectionLostMailTest.php
- M003: Ödeme başarısız
  - Event: app/Events/PaymentFailed.php
  - Listener: app/Listeners/SendPaymentFailedMail.php
  - Template: payment.failed
  - Dispatch: app/Http/Controllers/Payments/IyzicoCheckoutCallbackController.php::__invoke
  - Dedupe: error_code bazlı 30 dk
  - Test: tests/Feature/Mail/PaymentFailedMailTest.php
- M004: Trial bitti
  - Event: app/Events/TrialEnded.php
  - Listener: app/Listeners/SendTrialEndedMail.php
  - Template: trial.ended
  - Dispatch: app/Console/Commands/SubscriptionMaintenanceCommand.php::handle
  - Dedupe: user bazlı 24 saat
  - Test: tests/Feature/Mail/TrialEndedMailTest.php
- M005: Kota aşıldı
  - Event: app/Events/QuotaExceeded.php
  - Listener: app/Listeners/SendQuotaExceededMail.php
  - Template: quota.exceeded
  - Dispatch: app/Http/Controllers/Admin/ProductController.php::store
  - Dedupe: quota_key + period bazlı 24 saat
  - Test: tests/Feature/Mail/QuotaExceededMailTest.php
- M006: Ödeme başarılı
  - Event: app/Events/PaymentSucceeded.php
  - Listener: app/Listeners/SendPaymentSucceededMail.php
  - Template: payment.succeeded
  - Dispatch: app/Http/Controllers/Payments/IyzicoCheckoutCallbackController.php::__invoke
  - Dedupe: transaction_id varsa tekrar yok, yoksa occurred_at dakika bazlı
  - Test: tests/Feature/Mail/PaymentSucceededMailTest.php
- M007: Kota %80 uyarısı
  - Event: app/Events/QuotaWarningReached.php
  - Listener: app/Listeners/SendQuotaWarningMail.php
  - Template: quota.warning_80
  - Dispatch: app/Http/Controllers/Admin/ProductController.php::store
  - Dispatch: app/Http/Controllers/Admin/IntegrationController.php::update
  - Dispatch: app/Observers/OrderObserver.php::created
  - Dedupe: user + quota_type + period bazlı 7 gün
  - Test: tests/Feature/Mail/QuotaWarningReachedMailTest.php
- M008: Fatura oluşturuldu
  - Event: app/Events/InvoiceCreated.php
  - Listener: app/Listeners/SendInvoiceCreatedMail.php
  - Template: invoice.created
  - Dispatch: app/Services/EInvoices/EInvoiceService.php::issue
  - Dedupe: invoice_id bazlı
  - Test: tests/Feature/Mail/InvoiceCreatedMailTest.php
- M009: Fatura oluşturulamadı
  - Event: app/Events/InvoiceFailed.php
  - Listener: app/Listeners/SendInvoiceFailedMail.php
  - Template: invoice.failed
  - Dispatch: app/Services/EInvoices/EInvoiceService.php::issue (catch)
  - Dedupe: invoice_id bazlı
  - Test: tests/Feature/Mail/InvoiceFailedMailTest.php
- M010: Token bitişi yaklaşıyor
  - Event: app/Events/MarketplaceTokenExpiring.php
  - Listener: app/Listeners/SendMarketplaceTokenExpiringMail.php
  - Template: mp.token_expiring
  - Dispatch: app/Console/Commands/CheckMarketplaceTokenExpirationsCommand.php::handle
  - Dedupe: marketplace_credential_id + days_left bazlı 24 saat
  - Test: tests/Feature/Mail/MarketplaceTokenExpiringMailTest.php
- M011: Abonelik başladı
  - Event: app/Events/SubscriptionStarted.php
  - Listener: app/Listeners/SendSubscriptionStartedMail.php
  - Template: subscription.started
  - Dispatch: app/Http/Controllers/SubscriptionController.php::store, app/Http/Controllers/SubscriptionController.php::renew
  - Dedupe: subscription_id bazlı
  - Test: tests/Feature/Mail/SubscriptionStartedMailTest.php
- M012: Abonelik yenilendi
  - Event: app/Events/SubscriptionRenewed.php
  - Listener: app/Listeners/SendSubscriptionRenewedMail.php
  - Template: subscription.renewed
  - Dispatch: app/Http/Controllers/SubscriptionController.php::store
  - Dedupe: subscription_id + period_end veya renewed_at bazlı
  - Test: tests/Feature/Mail/SubscriptionRenewedMailTest.php

Not:
- subscription.cancelled senaryosu ayrıca mevcut.
  - Event: app/Events/SubscriptionCancelled.php
  - Listener: app/Listeners/SendSubscriptionCancelledMail.php
  - Template: subscription.cancelled
  - Dispatch: app/Http/Controllers/SubscriptionController.php::cancel
  - Dedupe: subscription_id bazlı
  - Test: tests/Feature/Mail/SubscriptionCancelledMailTest.php

## Scheduler
- marketplace:check-token-expirations dailyAt 09:00 Europe/Istanbul

## Bildirim Merkezi (Notification Hub)
- Tablolar (migration):
  - app_notifications
  - notification_preferences
  - notification_audit_logs
- Modeller/Enumlar/Servisler:
  - app/Models/Notification.php
  - app/Models/NotificationPreference.php
  - app/Models/NotificationAuditLog.php
  - app/Enums/NotificationType.php
  - app/Enums/NotificationChannel.php
  - app/Enums/NotificationSource.php

## Sistem Ayarları (Super Admin)
- Mail & Bildirim Ayarları sekmesi eklendi.
- system_settings tablosu + SettingsRepository ile DB tabanlı mail override ve test mail akışı.
- Incident & SLA ayarları artık super-admin Sistem Ayarları’ndan yönetilir; incident_sla config defaultları override edilir.
- Integration Health eşikleri artık super-admin Sistem Ayarları’ndan yönetilir; integration_health config defaultları DB ile override edilir.
- Feature gating: plan bazli plan_matrix (system_settings: features.plan_matrix), FeatureGate + EnsureFeatureEnabled ve admin upgrade ekranı.
- Billing MVP eklendi: plan katalogu system_settings billing.plans_catalog; admin billing/plans + checkout stub + success ile tenant plan_code guncellenir (user.plan_code legacy).
- Iyzico checkout init eklendi: iyzico.enabled true ise checkout iframe formu basiliyor; callback/webhook sonraki adimda.
- Iyzico callback + webhook eklendi: callback token ile API retrieve dogrular, webhook signature HMAC ile dogrular ve idempotent completion yapar. Webhook secret olmadan 400 doner.
- Iyzico Subscription MVP eklendi: billing_subscriptions + billing_subscription_events, abonelik webhook'u ile retrieveSubscription dogrular. Subscription ozelligi hesapta aktif olmalidir (sandbox dahil). Upgrade ayni product + ayni interval kurali. Webhook idempotent; downgrade politikasi MVP: canceled/unpaid -> free.
- Son is: Subscription testleri fixlendi (SDK model method uyumu), webhook imza testinde header gonderimi duzeltildi. Tum ilgili testler PASS.
- Incident Inbox eklendi: unassigned varsayilan filtre, quick assign/ack aksiyonlari ve SLA filtreleri (incident_sla aciksa).
  - app/Services/Notifications/NotificationService.php
  - app/Services/Notifications/PreferenceResolver.php
  - app/Services/Notifications/DedupeService.php
  - app/Jobs/SendNotificationEmailJob.php
  - app/Mail/NotificationHubMail.php
  - resources/views/emails/notification-hub.blade.php
- Event -> Listener örnekleri:
  - app/Events/OrderSyncFailed.php -> app/Listeners/NotificationHub/SendOrderSyncFailedNotification.php
  - app/Events/StockSyncFailed.php -> app/Listeners/NotificationHub/SendStockSyncFailedNotification.php
  - app/Events/InvoiceCreationFailed.php -> app/Listeners/NotificationHub/SendInvoiceCreationFailedNotification.php
  - app/Events/MarketplaceTokenExpired.php -> app/Listeners/NotificationHub/SendMarketplaceTokenExpiredNotification.php
  - app/Events/WebhookSignatureInvalid.php -> app/Listeners/NotificationHub/SendWebhookSignatureInvalidNotification.php
- Policy/Composer:
  - app/Policies/NotificationPolicy.php
  - app/Policies/NotificationPreferencePolicy.php
  - app/Providers/AppServiceProvider.php: bildirim sayacı + bell route paylaşımı
- Routes (notification-hub namespace):
  - routes/customer.php: admin.notification-hub.*
  - routes/admin.php: super-admin.notification-hub.*
- Views:
  - resources/views/partials/notification-bell.blade.php
  - resources/views/admin/notification-hub/index.blade.php
  - resources/views/admin/notification-hub/partials/_filters.blade.php
  - resources/views/admin/notification-hub/partials/_item.blade.php
  - resources/views/admin/notification-hub/preferences.blade.php
  - super-admin notification hub view
- Layout include:
  - resources/views/layouts/admin.blade.php
  - super-admin layout view
- Middleware/support izinleri:
  - config/support.php: admin.notification-hub.notifications.index + admin.notification-hub.preferences.index
  - app/Http/Middleware/EnsureSubUserPermission.php: admin.notification-hub.* => settings
- Varsayılan filtre:
  - Son 30 gün (admin + super-admin)
- Factories & Tests:
  - database/factories/NotificationFactory.php
  - database/factories/NotificationPreferenceFactory.php
  - tests/Feature/Notifications/NotificationHubTest.php
- README notu:
  - README.md Notification Hub bölümü eklendi

## Son İşlemler
- Prod readiness runbook eklendi:
  - docs/PROD.md (deployment, queue/scheduler, webhook, monitoring, backups, domain/SSL, smoke test)
- php artisan test: PASS (242 tests).
- Portal fatura ekranları terminoloji sadeleştirildi:
  - resources/views/customer/invoices/index.blade.php (Faturalar, kolonlar: Tarih/Tutar/Durum/Dönem, Detay)
  - resources/views/customer/invoices/show.blade.php (Fatura Detayı, PDF İndir vb.)
- Portal billing/çeşitli view ve servislerde bozuk ?? ifadeleri temizlendi (parse error fixleri):
  - NotificationService, BillingEventLogger, IyzicoClient, SettingsController, input-label, register, admin billing/plans, einvoices pdf vb.
- Queueable çakışması fix:
  - app/Jobs/SyncMarketplaceCategoriesJob.php: $queue property kaldırıldı, constructor’da onQueue('integrations')
- php artisan test: PASS (pdo_sqlite olmayan ortamlarda bazı testler WARN/skip).
- Admin Billing Events izleme ekranı eklendi:
  - routes/customer.php: /admin/observability/billing-events (super_admin)
  - app/Http/Controllers/Admin/BillingEventController.php
  - resources/views/admin/observability/billing_events/index|show.blade.php
  - tests: BillingEventAdminIndexTest, BillingEventAdminFiltersTest
- Observability eklendi:
  - CorrelationIdMiddleware + correlation alias + AppServiceProvider queue/command propagation.
  - billing_events tablosu + BillingEvent modeli + BillingEventLogger servisi.
  - Iyzico webhook/dunning ve invoice create/paid noktalarında billing event loglama.
- Customer invoice portal eklendi:
  - routes/customer.php altında invoices list/show/download (signed+throttle).
  - InvoicePolicy + Customer InvoiceController + customer invoice blade’leri.
- API token expired davranışı düzeltildi:
  - EnsureApiTokenValid middleware priority auth öncesine alındı (bootstrap/app.php).
- Testler eklendi ve çalıştırıldı:
  - CustomerInvoicePortalTest, InvoiceDownloadSignedTest, BillingCorrelationIdTest.
  - php artisan test: PASS (sqlite uyarıları bazı webhook testlerinde).
- Billing dunning/grace period eklendi:
  - billing_subscriptions: past_due_since, grace_until, last_dunning_sent_at + index
  - Sistem Ayarları (billing) dunning alanları + UI
  - Iyzico webhook sync: UNPAID/PAST_DUE grace set, ACTIVE reset, CANCELED downgrade
  - Command: billing:dunning-run (hourly) + reminder/downgrade notifications
  - Tests: tests/Feature/SubscriptionDunningTest.php
- Iyzico catalog auto-create eklendi (super-admin billing plans):
  - IyzicoSubscriptionClient createProduct/createPricingPlan
  - Routes: super-admin.system-settings.billing.iyzico.*
  - UI: billing_plans partial butonlar + JS (product/pricing ref otomatik doldurma)
  - Test: tests/Feature/SuperAdminIyzicoCatalogAutoCreateTest.php
- Not: Pricing plan interval MVP olarak MONTHLY sabit.
- Not: Upgrade stratejisi için tek product + çok pricing plan önerilir.
- Iyzico card update callback hardening:
  - token/state zorunlu ve provider_token ile esleme
  - duplicate callback ignore + BillingEvent log (card_update.callback_duplicate)
  - bilinmeyen token ignore + BillingEvent log (card_update.callback_unknown_token)
  - billing_events.tenant_id artik nullable; unknown/duplicate eventlerde tenant_id null yazilir
- Testler eklendi:
  - tests/Feature/IyzicoCardUpdateCallbackUnknownTokenTest.php
  - tests/Feature/IyzicoCardUpdateCallbackDuplicateTest.php
- php artisan test: PASS (sqlite uyarilari bazı webhook testlerinde).
- app_notifications hatası için migration çalıştırıldı:
  - php artisan migrate
- ParseError fix:
  - super-admin plans edit view: fazla @endforeach kaldırıldı.
- Notification Hub deliverability testleri eklendi:
  - tests/Feature/NotificationHubDeliverabilityTest.php
- Quiet hours için email job dispatch gecikmesi eklendi:
  - app/Services/Notifications/NotificationService.php
- Email job retry/backoff ayarları eklendi:
  - app/Jobs/SendNotificationEmailJob.php
- Email suppression altyapısı eklendi (DB + servis + admin ekran):
  - database/migrations/2026_02_05_120000_create_email_suppressions_table.php
  - app/Models/EmailSuppression.php
  - app/Services/Notifications/EmailSuppressionService.php
  - app/Http/Controllers/Admin/NotificationSuppressionController.php
  - resources/views/admin/notification-hub/suppressions/*
- Notification audit log action enum genişletildi:
  - database/migrations/2026_02_05_120010_expand_notification_audit_log_actions.php
- Suppression + audit log entegrasyonu (dispatch/defer/fail):
  - app/Services/Notifications/NotificationService.php
  - app/Jobs/SendNotificationEmailJob.php
- Email suppression testleri eklendi:
  - tests/Feature/EmailSuppressionTest.php
- Integration Health (MVP) eklendi:
  - config/integration_health.php (stale_minutes, degraded_error_threshold, window_hours)
  - app/Services/IntegrationHealthService.php (app_notifications + stock last_sync_at + token_expires_at)
  - resources/views/admin/integrations/health/*
  - routes: admin.integrations.health
  - tests/Feature/IntegrationHealthTest.php
- Integration Health bildirimleri eklendi:
  - app/Services/IntegrationHealthNotifier.php
  - app/Console/Commands/IntegrationHealthNotify.php
  - routes/console.php schedule: integrations:health-notify (10 dk)
  - tests/Feature/IntegrationHealthNotificationTest.php
- Incident (Olay) sistemi eklendi:
  - database/migrations/2026_02_05_150000_create_incidents_table.php
  - database/migrations/2026_02_05_150010_create_incident_events_table.php
  - database/migrations/2026_02_05_150020_expand_notification_audit_log_actions_for_incidents.php
  - app/Models/Incident.php, app/Models/IncidentEvent.php
  - app/Services/IncidentService.php
  - app/Services/IntegrationHealthNotifier.php (incident open/touch + event + incident_id data)
  - resources/views/admin/incidents/*
  - routes: admin.incidents.*
  - tests/Feature/IncidentTest.php
- Incident SLA/Ownership (MVP) eklendi:
  - config/incident_sla.php (ack/resolve SLA)
  - incidents: assigned_to_user_id + acknowledged_at
  - admin UI owner + SLA badge + MTTA/MTTR
  - tests/Feature/IncidentSlaOwnershipTest.php
- Audit action artik string; enum kullanilmiyor.

## Next steps
- Kullanıcı bildirirse kalan Türkçe karakter/encoding sorunlarını noktasal düzelt.
- Bildirim merkezi ekranlarında kalan UX/iyileştirmeler varsa toparla.
- Super-admin panel düzenleme (menü ve sayfa tutarlılığı) için kapsam çıkar.
- Gerekirse Notification Hub için ek testler (UI route 200) ekle.
- Yeni deliverability testlerini çalıştır:
  - php artisan test --filter=NotificationHubDeliverabilityTest
- Email suppression testlerini çalıştır:
  - php artisan test --filter=EmailSuppressionTest
- Integration Health testlerini çalıştır:
  - php artisan test --filter=IntegrationHealthTest

## Current Work (2026-02-07)
- Goal: Order profitability modular calculation + report service.
- Status: Domain layer, resolvers, config, and unit tests added.

## Changes Made
- Added profitability domain (DTOs, contracts, calculators, resolvers) and Decimal helper.
- Added marketplace config defaults for platform service fee and desi pricing.
- Bound profitability resolvers/calculators in AppServiceProvider.
- Added OrderProfitabilityReportService.
- Added unit tests for profitability scenarios.
- Added module catalog entry for order profitability report:
  - `feature.reports.profitability` in `database/seeders/ModuleCatalogSeeder.php`.
- Added `feature.reports.profitability` to Professional and Enterprise plans in `database/seeders/PlanSeeder.php`.
- Ran seeders:
  - `php artisan db:seed --class=ModuleCatalogSeeder`
  - `php artisan db:seed --class=PlanSeeder`
- Started dev server in a new terminal:
  - `php artisan serve --host=127.0.0.1 --port=8020`

## Next Steps
- Run: php artisan test --filter=ProfitabilityCalculatorTest

## Current Work (2026-02-08)
- Goal: Marketplace Profitability Dashboard (Phase 1-5 in progress).
- Status: Phase 1 models+migrations, Phase 2 adapters, Phase 3 sync jobs+command+scheduler, Phase 4 calculator+mart builder, Phase 5 UI routes+controllers+views implemented.

## Changes Made
- Added profitability tables and models:
  - `marketplace_accounts`, `raw_marketplace_events`, `core_order_items`, `mart_profitability_daily`.
  - Models: `MarketplaceAccount`, `RawMarketplaceEvent`, `CoreOrderItem`, `MartProfitabilityDaily`.
- Added marketplace adapter layer:
  - `app/Integrations/Marketplaces/*` interface, resolver, base adapter, DTOs, DateRange helpers.
  - Adapters: Trendyol, Hepsiburada, N11, Amazon (stubbed).
- Added sync jobs + dispatcher + command:
  - Jobs: `SyncMarketplaceOrdersJob`, `SyncMarketplaceReturnsJob`, `SyncMarketplaceFeesJob` (queue: integrations).
  - Dispatcher: `MarketplaceSyncDispatcher`.
  - Command: `marketplaces:sync`.
  - Scheduler: hourly orders/returns, daily fees (configurable via `config/marketplace_profitability.php`).
- Added profitability calculations:
  - `CoreProfitabilityCalculator`, `MartBuilder`.
- Added UI + routes:
  - Routes under `portal.profitability.*`.
  - Controllers: `ProfitabilityController`, `ProfitabilityAccountController`.
  - Views: `resources/views/admin/profitability/*`.
  - Sidebar nav entry for Kârlılık.
  - Sub-user permission mapping for `portal.profitability.*`.
- Refreshed admin + customer portal skin with a pastel coral/mint palette in `resources/views/layouts/admin.blade.php`.
- Updated KPI card gradients and chart colors in `resources/views/admin/profitability/index.blade.php`.
- Updated admin dashboard KPI card gradients and map label colors in `resources/views/admin/dashboard.blade.php`.
- Iterated admin/customer layout styling: grayscale palette, sidebar sizing, list row emphasis, active menu highlighting, and submenu persistence in `resources/views/layouts/admin.blade.php`.
- Added active state routing to all admin/customer sidebar links in `resources/views/layouts/admin.blade.php`.

## Next Steps
- Phase 6/7: Security review, tests (if desired), and finalize deliverables.
- Implement real marketplace API calls in adapters (roadmap prepared).
- Manuel doğrulama: bir marketplace hesabında `base_url` değerini `http://127.0.0.1` veya allowlist dışı bir host yapın, "Bağlantı testi" çalıştırın; generic hata dönmeli ve logda "Marketplace base_url rejected" görünmeli.





## Current Work (2026-02-22 - Hakediş Visibility Hotfix)
- Goal: Make Hakediş module truly visible/activatable across SuperAdmin + Client + user flows.
- Status: Root causes fixed and activation command hardened.

## Changes Made (2026-02-22 - Hakediş Visibility Hotfix)
- Added portal UI for settlements:
  - `portal.settlements.index|show|disputes` routes + controller + blade screens.
  - Sidebar entry: "Hakediş Kontrol Merkezi".
- Fixed feature key normalization and route gating:
  - API feature key standardized to `hakedis_module`.
  - `EnsureFeatureFlagEnabled` normalized with `Str::ascii`.
- Added module catalog + plan integration:
  - `feature.hakedis` added to `ModuleCatalogSeeder`.
  - `feature.hakedis` added to Starter/Professional/Enterprise plan module lists.
- Fixed entitlement behavior:
  - `EntitlementService::hasModule()` now falls back to active `user_modules` when no active plan exists.
- Added one-shot activation command:
  - `php artisan settlements:enable`
  - options: `--user-id`, `--email`, `--tenant-id`, `--grant-tenant-users`, `--no-grant`, `--module-only`, `--flag-only`.
  - command now auto-creates tenant row if missing (for FK-safe `feature_flags` insert).
- Added guards to prevent 500 when tables are missing:
  - sidebar/menu and middleware/controller checks for `feature_flags` table existence.

## Known Runtime Notes (2026-02-22)
- If module still not visible:
  1) run migrations,
  2) run `php artisan settlements:enable --user-id=<id> --grant-tenant-users`,
  3) `php artisan optimize:clear`,
  4) re-login + hard refresh.

## Current Work (2026-02-24 - Hakediş Kontrol Merkezi 6 Madde Tamamlandı)
- Goal: Complete all 6 pending items for Hakediş Kontrol Merkezi and prepare clean continuation point for next session.
- Status: Completed; targeted tests are passing.

## Changes Made (2026-02-24 - 6 Madde)
- Portal actions added:
  - Reconcile action from payout detail.
  - CSV export for payout transactions.
  - Dispute status update action in disputes list.
- Reconciliation behavior split:
  - `executeOne(payoutId)` for single payout reconciliation.
  - `executeByAccount(accountId)` for batch/account reconciliation.
  - API payout reconcile endpoint now uses single payout flow.
- Sub-user permissions split for settlements:
  - `settlements.view`
  - `settlements.manage`
  - Route permission map updated accordingly.
- Dispute type classification expanded:
  - `MISSING_PAYMENT`, `COMMISSION_DIFF`, `SHIPPING_DIFF`, `VAT_DIFF`, `UNKNOWN_DEDUCTION`.
- Settlement dashboard summary cards added on portal index:
  - total expected, total paid, discrepancy count, open disputes, last reconciliation timestamp.
- Settlement portal views refreshed:
  - index/show/disputes pages updated for new actions and KPI summary.

## Tests/Validation (2026-02-24)
- Targeted run:
  - `php artisan test --filter="PortalSettlementCenterTest|DisputeTypeClassificationTest|ReconcileCreatesDisputeTest|PermissionEnforcementTest|BuildExpectedPayoutsActionTest|TenantIsolationPayoutsTest"`
- Result:
  - `10 passed`.

## Next Session Start Note (2026-02-24)
- We continue from this point in the evening.
- Context to recall at start:
  - "Hakediş Kontrol Merkezi için 6 madde tamamlandı; kaldığımız yerden devam ediyoruz."

## Current Work (2026-02-24 - Marketplace Connector Completion + Login Asset Fix)
- Goal: Complete real connector foundation for non-Trendyol marketplaces and stabilize login page rendering on local 8200.
- Status: Completed and committed; targeted tests are passing.

## Changes Made (2026-02-24 - Connector Completion)
- Extended Trendyol connector:
  - Implemented real `fetchReturns()` claims flow with chunking/pagination and normalized return payload mapping.
- Implemented real HTTP flow skeletons for:
  - `HepsiburadaConnector` (`orders`, `returns`, `payouts`, `payout-transactions`)
  - `N11Connector` (`orders`, `returns`, `payouts`, `payout-transactions`)
  - `AmazonConnector` (`orders`, `returns`, `payouts`, `payout-transactions`)
- Added configurable endpoint/page-size settings:
  - `config/marketplaces.php` expanded with per-marketplace endpoints and page sizes.
- Added/updated tests:
  - `tests/Feature/Marketplaces/TrendyolOrderSyncTest.php` (returns mapping test)
  - `tests/Feature/Marketplaces/OtherMarketplaceConnectorsTest.php` (HB/N11/Amazon normalize flows)
- Commit created:
  - `071b50b` - `Implement real connector flows for HB/N11/Amazon and extend Trendyol returns`

## Tests/Validation (2026-02-24 - Connector Completion)
- `php artisan test --filter="OtherMarketplaceConnectorsTest|TrendyolOrderSyncTest"` => `7 passed`
- `php artisan test --filter="PortalSettlementCenterTest|DisputeTypeClassificationTest|ReconcileCreatesDisputeTest|BuildExpectedPayoutsActionTest|TenantIsolationPayoutsTest"` => `9 passed`

## Runtime Hotfix (2026-02-24 - Login Page)
- Symptom:
  - Login page was unstyled (large Laravel logo only).
- Root cause:
  - `public/hot` existed and forced assets to `http://127.0.0.1:5174` while Vite dev server was not running.
- Fix:
  - Removed `public/hot` so Blade `@vite` resolves to `public/build/assets/*`.
  - Verified login HTML now points to `http://pazar.test:8200/build/assets/...`.

## Next Steps (2026-02-24)
1) Run manual marketplace sync smoke with real sandbox credentials for HB/N11/Amazon.
2) Replace placeholder auth builders with official signature/token flows per provider.
3) Decide whether connector skeleton commit (`071b50b`) should be pushed now or after integrated QA.

## Current Work (2026-02-24 - Hakediş Loss Finder v1.1 In Progress)
- Goal: Add confidence score, recurring patterns, one-click evidence pack, tenant rule override, regression guard.
- Status: Analysis and baseline scan completed; implementation started but not finalized in this session.

## Progress Snapshot (2026-02-24 - v1.1)
- Reviewed and confirmed v1.0 baseline files:
  - `ReconciliationService`, `LossFinderEngine`, `DisputeService`
  - `SettlementLossFinderController`, `DisputesController`, `routes/api.php`
  - Existing settlement schema migration (`2026_02_24_210000_add_loss_finder_settlement_schema.php`).
- Verified latest regression fixes from previous step are green:
  - `LossFinderWorkflowTest` and `ReconcileCreatesDisputeTest` passing.
  - Extended suite also passing (20 tests / 49 assertions).

## Next Session Plan (Start Here)
1) Add v1.1 migration set:
   - `reconciliations`: `findings_summary_json`, `run_hash`, `run_version` (+ index)
   - new `loss_findings` table
   - new `loss_patterns` table
   - `disputes`: evidence pack columns
   - `reconciliation_rules`: tenant override columns
   - `payouts`: regression flag/note
2) Add models/services/jobs:
   - `LossFinding`, `LossPattern`
   - `ConfidenceScoringService`, `LossPatternAggregatorService`, `EvidencePackService`, `TenantRuleResolver`, `ReconcileRegressionGuardService`
   - `GenerateEvidencePackJob`, `AggregateLossPatternsJob`, `RunRegressionGuardJob`
3) Wire reconcile flow updates:
   - dual-write findings (JSON + `loss_findings`)
   - summary JSON + run hash/version + idempotency check
   - dispatch post-reconcile event/jobs
4) Add API v1.1 endpoints:
   - patterns, findings filters, evidence pack create/get, regression, tenant rules upsert
5) Add tests:
   - unit tests for scoring/resolver/aggregator/regression
   - feature tests for patterns/findings filter/evidence pack/regression/rule override.

## Resume Prompt
- "Hakediş Loss Finder v1.1'de migration + service + endpoint + test implementasyonuna kaldığımız yerden devam et."
