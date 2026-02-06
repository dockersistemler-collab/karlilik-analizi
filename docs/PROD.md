# Production Readiness (app + sa + www)

## Checklist
- APP_URL, APP_ROOT_DOMAIN, APP_APP_DOMAIN, APP_SA_DOMAIN dogru mu?
- SESSION_DOMAIN=null (host-only cookie), APP_SESSION_COOKIE ve SA_SESSION_COOKIE ayarli mi?
- DB/REDIS/QUEUE/MAIL/IYZICO env degerleri production ile uyumlu mu?
- QUEUE_CONNECTION=database veya redis (production'da sync olamaz).
- Storage symlink var mi? `php artisan storage:link`
- Migrations calisti mi? `php artisan migrate --force`
- Cache'ler guncel mi? `php artisan config:cache` `php artisan route:cache` `php artisan view:cache`
- Queue worker ve scheduler calisiyor mu?
- Domain + SSL + HSTS ayarlari tamam mi?

## Deployment Adimlari
Env degiskenleri:
- APP_URL=https://app.<root>
- APP_ROOT_DOMAIN=<root>
- APP_APP_DOMAIN=app.<root>
- APP_SA_DOMAIN=sa.<root>
- SESSION_DOMAIN=null
- APP_SESSION_COOKIE=app_session
- SA_SESSION_COOKIE=sa_session
- DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD
- REDIS_HOST, REDIS_PORT, REDIS_PASSWORD (varsa)
- QUEUE_CONNECTION=database veya redis
- MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME
- PAYMENTS_MODE=iyzico
- IYZICO_API_KEY, IYZICO_SECRET_KEY, IYZICO_BASE_URL, IYZICO_WEBHOOK_SECRET

Deploy adimlari:
1. `php artisan migrate --force`
2. `php artisan config:cache`
3. `php artisan route:cache`
4. `php artisan view:cache`
5. `php artisan storage:link`

Queue worker:
- `php artisan queue:work --queue=default,webhooks,integrations --tries=3 --backoff=5`

Scheduler (cron):
- `* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1`

## Queue & Scheduler (Kritik)
Kritik akislar:
- Webhook processing (webhooks queue)
- Billing dunning (billing:dunning-run)
- E-invoice otomasyonlari (invoice.*)
- Marketplace category sync (integrations queue)

Retry/Backoff:
- Webhook ve odeme akislari icin kucuk backoff (5-30s) + 3-5 deneme.
- Integrations icin daha uzun backoff (30-120s) + 5-10 deneme.

Failed jobs:
- `php artisan queue:failed`
- `php artisan queue:retry <id|all>`
- `php artisan queue:flush` (dikkatli kullan)

## Webhook Guvenligi ve Dayaniklilik
Callback URL:
- Iyzico callback ve webhook endpointleri root domain uzerinden calisir.
- Ornek: `https://<root>/payments/iyzico/callback`, `https://<root>/webhooks/iyzico/payment`

Throttle:
- Uygun rate limit ve IP allowlist opsiyonel.

Idempotency:
- Provider token/ref bazli tekrar eden istekler noop olmali.

Logging:
- Correlation ID tum request/joblarda tasinir.

Observability reprocess:
- `config/observability.php` allowlist ve idempotency window ayarlari kontrol edilmeli.

## Logging & Monitoring
- billing_events tablosu: odeme ve webhook akisi izleme.
- Log channel: daily (rotasyon kontrolu).
- Sentry/OTel opsiyonel entegrasyon.
- Health endpoint: `GET /up` (load balancer ve uptime check icin).
- Integration health: Admin panelden entegrasyon sagligi kontrolu.

## Backups ve Veri Guvenligi
- DB backup: Gunluk snapshot + saklama politikasi (en az 7-14 gun).
- Storage backup: Invoice PDF ve yuklemeler dahil.
- KVKK: Support View loglari ve erisim kayitlari korunmali.

## Domain / SSL / Nginx
- app.<root> -> Laravel client panel
- sa.<root> -> Laravel super-admin panel
- www.<root> -> WordPress (Laravel route olmasin)
- HTTPS zorunlu, HSTS onerilir (prod).

## Go-Live Smoke Test (10 dk)
1. app login
2. Subscription aktif mi?
3. Card update akisi (sandbox)
4. Invoice list ve download
5. Webhook receive/process
6. BillingEvent + correlation kontrolu
7. sa tarafinda reprocess butonu
