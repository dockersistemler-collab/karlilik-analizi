# Session Memory

**Last updated:** 2026-02-17

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

## Mail Altyapýsý Özeti
- Akýþ: Event -> Listener (ShouldQueue) -> MailSender -> mail_logs
- Policy hook: MailPolicyService + mail_rule_assignments
- Admin panel: mail loglarý ve mail template yönetimi

## Tamamlanan Mail Senaryolarý (M001-M012)
- M001: Support view bildirimi
  - Event: app/Events/SupportViewStarted.php
  - Listener: app/Listeners/SendSupportViewStartedMail.php
  - Template: security.support_view_used
  - Dispatch: app/Services/Admin/SupportViewService.php::start
  - Dedupe: yok
  - Test: tests/Feature/Mail/SupportViewStartedMailTest.php
- M002: Pazaryeri baðlantýsý koptu
  - Event: app/Events/MarketplaceConnectionLost.php
  - Listener: app/Listeners/SendMarketplaceConnectionLostMail.php
  - Template: mp.connection_lost
  - Dispatch: app/Services/Marketplace/Category/TrendyolCategoryProvider.php::fetchCategoryTree
  - Dedupe: yok
  - Test: tests/Feature/Mail/MarketplaceConnectionLostMailTest.php
- M003: Ödeme baþarýsýz
  - Event: app/Events/PaymentFailed.php
  - Listener: app/Listeners/SendPaymentFailedMail.php
  - Template: payment.failed
  - Dispatch: app/Http/Controllers/Payments/IyzicoCheckoutCallbackController.php::__invoke
  - Dedupe: error_code bazlý 30 dk
  - Test: tests/Feature/Mail/PaymentFailedMailTest.php
- M004: Trial bitti
  - Event: app/Events/TrialEnded.php
  - Listener: app/Listeners/SendTrialEndedMail.php
  - Template: trial.ended
  - Dispatch: app/Console/Commands/SubscriptionMaintenanceCommand.php::handle
  - Dedupe: user bazlý 24 saat
  - Test: tests/Feature/Mail/TrialEndedMailTest.php
- M005: Kota aþýldý
  - Event: app/Events/QuotaExceeded.php
  - Listener: app/Listeners/SendQuotaExceededMail.php
  - Template: quota.exceeded
  - Dispatch: app/Http/Controllers/Admin/ProductController.php::store
  - Dedupe: quota_key + period bazlý 24 saat
  - Test: tests/Feature/Mail/QuotaExceededMailTest.php
- M006: Ödeme baþarýlý
  - Event: app/Events/PaymentSucceeded.php
  - Listener: app/Listeners/SendPaymentSucceededMail.php
  - Template: payment.succeeded
  - Dispatch: app/Http/Controllers/Payments/IyzicoCheckoutCallbackController.php::__invoke
  - Dedupe: transaction_id varsa tekrar yok, yoksa occurred_at dakika bazlý
  - Test: tests/Feature/Mail/PaymentSucceededMailTest.php
- M007: Kota %80 uyarýsý
  - Event: app/Events/QuotaWarningReached.php
  - Listener: app/Listeners/SendQuotaWarningMail.php
  - Template: quota.warning_80
  - Dispatch: app/Http/Controllers/Admin/ProductController.php::store
  - Dispatch: app/Http/Controllers/Admin/IntegrationController.php::update
  - Dispatch: app/Observers/OrderObserver.php::created
  - Dedupe: user + quota_type + period bazlý 7 gün
  - Test: tests/Feature/Mail/QuotaWarningReachedMailTest.php
- M008: Fatura oluþturuldu
  - Event: app/Events/InvoiceCreated.php
  - Listener: app/Listeners/SendInvoiceCreatedMail.php
  - Template: invoice.created
  - Dispatch: app/Services/EInvoices/EInvoiceService.php::issue
  - Dedupe: invoice_id bazlý
  - Test: tests/Feature/Mail/InvoiceCreatedMailTest.php
- M009: Fatura oluþturulamadý
  - Event: app/Events/InvoiceFailed.php
  - Listener: app/Listeners/SendInvoiceFailedMail.php
  - Template: invoice.failed
  - Dispatch: app/Services/EInvoices/EInvoiceService.php::issue (catch)
  - Dedupe: invoice_id bazlý
  - Test: tests/Feature/Mail/InvoiceFailedMailTest.php
- M010: Token bitiþi yaklaþýyor
  - Event: app/Events/MarketplaceTokenExpiring.php
  - Listener: app/Listeners/SendMarketplaceTokenExpiringMail.php
  - Template: mp.token_expiring
  - Dispatch: app/Console/Commands/CheckMarketplaceTokenExpirationsCommand.php::handle
  - Dedupe: marketplace_credential_id + days_left bazlý 24 saat
  - Test: tests/Feature/Mail/MarketplaceTokenExpiringMailTest.php
- M011: Abonelik baþladý
  - Event: app/Events/SubscriptionStarted.php
  - Listener: app/Listeners/SendSubscriptionStartedMail.php
  - Template: subscription.started
  - Dispatch: app/Http/Controllers/SubscriptionController.php::store, app/Http/Controllers/SubscriptionController.php::renew
  - Dedupe: subscription_id bazlý
  - Test: tests/Feature/Mail/SubscriptionStartedMailTest.php
- M012: Abonelik yenilendi
  - Event: app/Events/SubscriptionRenewed.php
  - Listener: app/Listeners/SendSubscriptionRenewedMail.php
  - Template: subscription.renewed
  - Dispatch: app/Http/Controllers/SubscriptionController.php::store
  - Dedupe: subscription_id + period_end veya renewed_at bazlý
  - Test: tests/Feature/Mail/SubscriptionRenewedMailTest.php

Not:
- subscription.cancelled senaryosu ayrýca mevcut.
  - Event: app/Events/SubscriptionCancelled.php
  - Listener: app/Listeners/SendSubscriptionCancelledMail.php
  - Template: subscription.cancelled
  - Dispatch: app/Http/Controllers/SubscriptionController.php::cancel
  - Dedupe: subscription_id bazlý
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

## Sistem Ayarlarý (Super Admin)
- Mail & Bildirim Ayarlarý sekmesi eklendi.
- system_settings tablosu + SettingsRepository ile DB tabanlý mail override ve test mail akýþý.
- Incident & SLA ayarlarý artýk super-admin Sistem Ayarlarý’ndan yönetilir; incident_sla config defaultlarý override edilir.
- Integration Health eþikleri artýk super-admin Sistem Ayarlarý’ndan yönetilir; integration_health config defaultlarý DB ile override edilir.
- Feature gating: plan bazli plan_matrix (system_settings: features.plan_matrix), FeatureGate + EnsureFeatureEnabled ve admin upgrade ekraný.
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
  - app/Providers/AppServiceProvider.php: bildirim sayacý + bell route paylaþýmý
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
- Varsayýlan filtre:
  - Son 30 gün (admin + super-admin)
- Factories & Tests:
  - database/factories/NotificationFactory.php
  - database/factories/NotificationPreferenceFactory.php
  - tests/Feature/Notifications/NotificationHubTest.php
- README notu:
  - README.md Notification Hub bölümü eklendi

## Son Ýþlemler
- Prod readiness runbook eklendi:
  - docs/PROD.md (deployment, queue/scheduler, webhook, monitoring, backups, domain/SSL, smoke test)
- php artisan test: PASS (242 tests).
- Portal fatura ekranlarý terminoloji sadeleþtirildi:
  - resources/views/customer/invoices/index.blade.php (Faturalar, kolonlar: Tarih/Tutar/Durum/Dönem, Detay)
  - resources/views/customer/invoices/show.blade.php (Fatura Detayý, PDF Ýndir vb.)
- Portal billing/çeþitli view ve servislerde bozuk ?? ifadeleri temizlendi (parse error fixleri):
  - NotificationService, BillingEventLogger, IyzicoClient, SettingsController, input-label, register, admin billing/plans, einvoices pdf vb.
- Queueable çakýþmasý fix:
  - app/Jobs/SyncMarketplaceCategoriesJob.php: $queue property kaldýrýldý, constructor’da onQueue('integrations')
- php artisan test: PASS (pdo_sqlite olmayan ortamlarda bazý testler WARN/skip).
- Admin Billing Events izleme ekraný eklendi:
  - routes/customer.php: /admin/observability/billing-events (super_admin)
  - app/Http/Controllers/Admin/BillingEventController.php
  - resources/views/admin/observability/billing_events/index|show.blade.php
  - tests: BillingEventAdminIndexTest, BillingEventAdminFiltersTest
- Observability eklendi:
  - CorrelationIdMiddleware + correlation alias + AppServiceProvider queue/command propagation.
  - billing_events tablosu + BillingEvent modeli + BillingEventLogger servisi.
  - Iyzico webhook/dunning ve invoice create/paid noktalarýnda billing event loglama.
- Customer invoice portal eklendi:
  - routes/customer.php altýnda invoices list/show/download (signed+throttle).
  - InvoicePolicy + Customer InvoiceController + customer invoice blade’leri.
- API token expired davranýþý düzeltildi:
  - EnsureApiTokenValid middleware priority auth öncesine alýndý (bootstrap/app.php).
- Testler eklendi ve çalýþtýrýldý:
  - CustomerInvoicePortalTest, InvoiceDownloadSignedTest, BillingCorrelationIdTest.
  - php artisan test: PASS (sqlite uyarýlarý bazý webhook testlerinde).
- Billing dunning/grace period eklendi:
  - billing_subscriptions: past_due_since, grace_until, last_dunning_sent_at + index
  - Sistem Ayarlarý (billing) dunning alanlarý + UI
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
- php artisan test: PASS (sqlite uyarilari bazý webhook testlerinde).
- app_notifications hatasý için migration çalýþtýrýldý:
  - php artisan migrate
- ParseError fix:
  - super-admin plans edit view: fazla @endforeach kaldýrýldý.
- Notification Hub deliverability testleri eklendi:
  - tests/Feature/NotificationHubDeliverabilityTest.php
- Quiet hours için email job dispatch gecikmesi eklendi:
  - app/Services/Notifications/NotificationService.php
- Email job retry/backoff ayarlarý eklendi:
  - app/Jobs/SendNotificationEmailJob.php
- Email suppression altyapýsý eklendi (DB + servis + admin ekran):
  - database/migrations/2026_02_05_120000_create_email_suppressions_table.php
  - app/Models/EmailSuppression.php
  - app/Services/Notifications/EmailSuppressionService.php
  - app/Http/Controllers/Admin/NotificationSuppressionController.php
  - resources/views/admin/notification-hub/suppressions/*
- Notification audit log action enum geniþletildi:
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
- Kullanýcý bildirirse kalan Türkçe karakter/encoding sorunlarýný noktasal düzelt.
- Bildirim merkezi ekranlarýnda kalan UX/iyileþtirmeler varsa toparla.
- Super-admin panel düzenleme (menü ve sayfa tutarlýlýðý) için kapsam çýkar.
- Gerekirse Notification Hub için ek testler (UI route 200) ekle.
- Yeni deliverability testlerini çalýþtýr:
  - php artisan test --filter=NotificationHubDeliverabilityTest
- Email suppression testlerini çalýþtýr:
  - php artisan test --filter=EmailSuppressionTest
- Integration Health testlerini çalýþtýr:
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
  - Sidebar nav entry for Kârlýlýk.
  - Sub-user permission mapping for `portal.profitability.*`.
- Refreshed admin + customer portal skin with a pastel coral/mint palette in `resources/views/layouts/admin.blade.php`.
- Updated KPI card gradients and chart colors in `resources/views/admin/profitability/index.blade.php`.
- Updated admin dashboard KPI card gradients and map label colors in `resources/views/admin/dashboard.blade.php`.
- Iterated admin/customer layout styling: grayscale palette, sidebar sizing, list row emphasis, active menu highlighting, and submenu persistence in `resources/views/layouts/admin.blade.php`.
- Added active state routing to all admin/customer sidebar links in `resources/views/layouts/admin.blade.php`.

## Next Steps
- Phase 6/7: Security review, tests (if desired), and finalize deliverables.
- Implement real marketplace API calls in adapters (roadmap prepared).
- Manuel doðrulama: bir marketplace hesabýnda `base_url` deðerini `http://127.0.0.1` veya allowlist dýþý bir host yapýn, "Baðlantý testi" çalýþtýrýn; generic hata dönmeli ve logda "Marketplace base_url rejected" görünmeli.


