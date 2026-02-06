# Entitlements Smoke Checklist

## Senaryo 1: Plan yok
- Kullanıcıda aktif plan yok.
- Beklenen: `module:*` gerektiren tüm sayfalar 403 veya pricing/upsell yönlendirmesi.

Kontrol:
- Raporlar: `/admin/reports`
- Exportlar: `/admin/orders-export`
- Entegrasyonlar: `/admin/integrations`
- Kargo Takip: `/admin/shipments`
- API Token: `/admin/settings/api`
- Webhook: `/admin/settings/webhooks`

## Senaryo 2: Plan var ama modül yok
- Kullanıcıda aktif plan var, ilgili `feature.*` modülü yok.
- Beklenen: ilgili sayfa 403 veya upsell yönlendirmesi.

Kontrol:
- `feature.reports` yok → Raporlar menüsü görünmez, `/admin/reports` 403.
- `feature.exports` yok → export endpointleri 403.
- `feature.integrations` yok → `/admin/integrations` 403.
- `feature.cargo_tracking` yok → `/admin/shipments` 403.
- `feature.einvoice_api` yok → `/admin/settings/api` token işlemleri 403.
- `feature.einvoice_webhooks` yok → `/admin/settings/webhooks` yönetimi 403.

## Senaryo 3: Plan var, user_modules ile modül var
- Plan modülü yok, fakat user_modules ile `feature.*` atanmış.
- Beklenen: menü görünür, sayfa erişilebilir.

Kontrol:
- `feature.reports` user_modules → Raporlar menüsü görünür, sayfalar açılır.
- `feature.exports` user_modules → export endpointleri çalışır.
- `integration.marketplace.trendyol` user_modules → Trendyol entegrasyon ekranına erişilir.
- `feature.cargo_tracking` user_modules → Kargo takip ekranı açılır.

## Notlar
- Legacy `plan_modules` alanı temizlendi.
- Temizlik migrasyonu: `2026_02_02_031000_clear_plan_modules_column.php`
