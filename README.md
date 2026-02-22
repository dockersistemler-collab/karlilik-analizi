<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Notification Hub

- Routes
- `admin.notification-hub.notifications.index` => `/admin/notification-hub/notifications`
- `admin.notification-hub.preferences.index` => `/admin/notification-hub/preferences`
- `super-admin.notification-hub.notifications.index` => `sa.<root>/notification-hub/notifications`

- Filter parametreleri
- `type` (critical|operational|info)
- `marketplace` (trendyol|hepsiburada|amazon|n11 vb.)
- `read` (read|unread)
- `from` / `to` (YYYY-MM-DD)

- Dedupe mantığı
- `dedupe_key` aynıysa ve 10 dk içinde tekrar geldiyse yeni kayıt açılmaz, mevcut kayıt `updated_at` güncellenir.

- Support View KVKK notu
- Support View açıkken yapılan görüntüleme/okuma/ayar değişiklikleri `notification_audit_logs` tablosuna yazılır.
 
## Testing 
 
- Test DB config: .env.testing (MySQL) 
- Create DB: mysql -e CREATE DATABASE IF NOT EXISTS pazaryeri_entegrasyon_test; 
- Run: php artisan test

- Session domain: SESSION_DOMAIN=null (host-only cookie). This avoids session leakage between app.* and sa.* subdomains.

## Hakedis Kontrol Merkezi (API v1)

Bu repoya multi-tenant hakediş/payout kontrol modulu eklendi.

### Kurulum

```bash
php artisan migrate
php artisan db:seed --class=HakedisKontrolMerkeziSeeder
php artisan queue:work --queue=integrations,default
php artisan horizon
```

### Ornek cURL

```bash
curl -X POST http://localhost:8200/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"tenantadmin@local.test","password":"password","device_name":"cli"}'

curl -X POST http://localhost:8200/api/v1/marketplace-accounts/1/sync \
  -H "Authorization: Bearer <TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"from":"2026-02-01","to":"2026-02-28","sync_mode":"sync"}'

curl -X GET "http://localhost:8200/api/v1/payouts?status=EXPECTED" \
  -H "Authorization: Bearer <TOKEN>"
```

### TODO

- Marketplace connectorlari su anda mock adapterdir; gercek API endpointleri sonradan entegre edilmelidir.
- Role endpointi fallback sabit dizi doner; `spatie/laravel-permission` baglantisi TODO.
- Export endpointi CSV'dir; XLSX placeholder olarak birakilmistir.

## RBAC (Spatie) ve Tenant Yetkileri

### Kurulum / Hazirlama

```bash
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan db:seed --class=RbacPermissionSeeder
```

### Roller

- `SuperAdmin`
- `TenantAdmin`
- `Finance`
- `Viewer`

### Permission Listesi

- `tenants.manage`
- `features.manage`
- `users.manage`
- `roles.manage`
- `marketplace_accounts.manage`
- `settlement_rules.manage`
- `sync.run`
- `payouts.view`
- `payouts.reconcile`
- `disputes.view`
- `disputes.manage`
- `exports.create`
- `dashboard.view`

### Ornek cURL (RBAC)

```bash
# SuperAdmin tenant olusturma (token user: SuperAdmin + tenants.manage)
curl -X POST http://localhost:8200/api/v1/tenants \
  -H "Authorization: Bearer <SUPERADMIN_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"name":"Acme Tenant","status":"active","plan":"pro"}'

# Tenant payout listeleme (token user: payouts.view + hakediş_module acik)
curl -X GET http://localhost:8200/api/v1/payouts \
  -H "Authorization: Bearer <TENANT_TOKEN>"

# Reconcile (token user: payouts.reconcile)
curl -X POST http://localhost:8200/api/v1/payouts/1/reconcile \
  -H "Authorization: Bearer <TENANT_TOKEN>"

# SuperAdmin tenant context ile tenant kaynaklarinda gezinme
curl -X GET http://localhost:8200/api/v1/payouts \
  -H "Authorization: Bearer <SUPERADMIN_TOKEN>" \
  -H "X-Tenant-Id: 12"
```

## Real Connector Iskeleti

- Connector registry artik defaultta real connector siniflarini kullanir (`config/marketplaces.php`).
- Mock fallback acmak icin:
  - `MARKETPLACES_USE_MOCK_CONNECTORS=true`
- Trendyol `fetchOrders` real HTTP akisi ornegi:
  - `Http::baseUrl(...)->withHeaders(...)->retry(...)->get(...)->json()`
- Tum connector request/response metasi `sync_logs` tablosuna sensitive alanlar maskelenerek yazilir.

### Trendyol credentials ornegi

`marketplace_accounts.credentials` icinde su alanlar beklenir:

```json
{
  "api_key": "xxxxx",
  "api_secret": "yyyyy",
  "seller_id": "123456",
  "store_front_code": "STORE_FRONT_CODE"
}
```

Not: Trendyol finance/order cagrilarinda `storeFrontCode` header zorunludur.

### Trendyol sync cURL

```bash
curl -X POST http://localhost:8200/api/v1/marketplace-accounts/1/sync \
  -H "Authorization: Bearer <TENANT_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{"from":"2026-02-01","to":"2026-02-28","sync_mode":"sync"}'
```

Bu akista Trendyol icin:
1. settlements (Sale + Return)
2. otherfinancials (PaymentOrder)
3. paymentOrderId bazli payout/payout_transactions normalizasyonu
calistirilir.

## Trendyol Orders Connector Notes

- Endpoint: `GET /integration/order/sellers/{sellerId}/orders`
- Required header: `storeFrontCode` (read from `marketplace_accounts.credentials.store_front_code`)
- Connector always sends `startDate` and `endDate` (milliseconds).
- If requested interval is larger than 14 days, connector auto-chunks into 14-day windows and paginates each chunk.
- Default order sync sort: `orderByField=PackageLastModifiedDate`, `orderByDirection=ASC`.
- `size` is clamped to max `200`.
- `shipmentPackageIds` max `50` and connector throws validation exception if exceeded.

## Hakediş Modülü Tek Komut

Hakediş modülünü (module + flag + entitlement) tek satırla açmak için:

```bash
php artisan settlements:enable --user-id=3
```

Alternatif:

```bash
php artisan settlements:enable --email=tenantadmin@local.test
php artisan settlements:enable --tenant-id=3 --no-grant
php artisan settlements:enable --tenant-id=3 --grant-tenant-users
```
