# E-Fatura API (v1) — Müşteri / Entegratör Dokümanı (TR)

Bu doküman, Pazaryeri Entegrasyon sistemindeki **E‑Fatura API**’yi dış sistemlerden (ERP, muhasebe yazılımı, entegratör servisleri) hızlıca kullanabilmeniz için hazırlanmıştır.

## API ne işe yarar?
- Hesabınızdaki **E‑Fatura** kayıtlarını listelemenizi ve detaylarını almanızı sağlar.
- Oluşturulan faturanın **PDF** çıktısını indirmenizi sağlar.
- Entegratör/provider tarafından oluşan durum değişikliklerini **provider-status** endpoint’i ile sisteme geri yazmanızı sağlar (event log oluşturur).

## Kimler kullanmalı?
- ERP/Muhasebe entegrasyonu yapan geliştiriciler
- E‑Fatura sağlayıcı/entegratör servisleri
- Kendi otomasyonlarını yazan teknik ekipler

## Önemli: Modül satın alınmadan API çalışmaz
API rotaları şu middleware’lerle korunur:
- `auth:sanctum` (Bearer token)
- `module:feature.einvoice_api` (modül aktif değilse API çalışmaz)

Bu nedenle **feature.einvoice_api (yıllık)** modülü satın alınmadan API çağrıları başarısız olur.

## Versiyonlama
- Base path: `/api/v1`

## Token oluşturma (Admin Panel)
1) Admin panelde `admin/settings/api` sayfasına gidin (API Erişimi).
2) Token adı ve yetkileri seçin.
3) Oluşan token **yalnızca bir kez** gösterilir. Güvenli bir yere kaydedin.

### Yetkiler (abilities)
- `einvoices:read` → Listeleme/Detay/PDF okumaları
- `einvoices:status` → `provider-status` endpoint’ini kullanma

Not: `provider-status` endpoint’i `einvoices:status` yetkisini zorunlu kılar.

## Kimlik Doğrulama (Bearer Token)
Tüm isteklerde header olarak Bearer token gönderin:

```bash
Authorization: Bearer <API_TOKEN>
Accept: application/json
```

Örnek (maskeli token):
```bash
export BASE_URL="http://localhost"
export API_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJ...REDACTED"
```

## İlk istek: Listeleme
`GET /api/v1/einvoices`

```bash
curl -sS "$BASE_URL/api/v1/einvoices" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

## Endpoint listesi

### 1) Listele
`GET /api/v1/einvoices`

**Query parametreleri**
- `status` (string, opsiyonel)
- `marketplace` (string, opsiyonel)
- `type` (string, opsiyonel)
- `updated_since` (datetime string, opsiyonel; ISO‑8601 önerilir)
- `per_page` (int, opsiyonel; 1–100 arası; default 20)
- `page` (int, opsiyonel; pagination için)

**Örnek**
```bash
curl -sS "$BASE_URL/api/v1/einvoices?status=issued&per_page=20&page=1" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

**200 Response (örnek)**
```json
{
  "data": [
    {
      "id": 123,
      "invoice_no": "EA-2026-000123",
      "status": "issued",
      "type": "sale",
      "issued_at": "2026-01-31T20:16:10Z",
      "marketplace": "trendyol",
      "marketplace_order_no": "TY-ORDER-001",
      "buyer": {
        "name": "Acme Ltd.",
        "email": "billing@acme.test",
        "phone": "+905300000000"
      },
      "totals": {
        "subtotal": 100.0,
        "tax_total": 20.0,
        "discount_total": 0.0,
        "grand_total": 120.0,
        "currency": "TRY"
      },
      "pdf_url": "http://localhost/api/v1/einvoices/123/pdf",
      "updated_at": "2026-01-31T20:20:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 1,
    "last_page": 1
  }
}
```

### 2) Detay
`GET /api/v1/einvoices/{einvoice}`

**Query parametreleri**
- `include_items` (bool, opsiyonel; default `true`)
- `include_events` (bool, opsiyonel; default `false`)

**Örnek**
```bash
curl -sS "$BASE_URL/api/v1/einvoices/123?include_items=1&include_events=1" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

**200 Response (örnek)**
```json
{
  "id": 123,
  "invoice_no": "EA-2026-000123",
  "status": "issued",
  "type": "sale",
  "issued_at": "2026-01-31T20:16:10Z",
  "marketplace": "trendyol",
  "marketplace_order_no": "TY-ORDER-001",
  "buyer": { "name": "Acme Ltd.", "email": "billing@acme.test", "phone": "+905300000000" },
  "totals": { "subtotal": 100.0, "tax_total": 20.0, "discount_total": 0.0, "grand_total": 120.0, "currency": "TRY" },
  "pdf_url": "http://localhost/api/v1/einvoices/123/pdf",
  "updated_at": "2026-01-31T20:20:00Z",
  "items": [
    {
      "id": 1,
      "sku": "SKU-1",
      "name": "Ürün 1",
      "quantity": 1.0,
      "unit_price": 100.0,
      "vat_rate": 20.0,
      "vat_amount": 20.0,
      "discount_amount": 0.0,
      "total": 120.0
    }
  ],
  "events": [
    {
      "id": 10,
      "type": "provider_status_updated",
      "payload": { "provider_status": "accepted", "provider_invoice_id": "PRV-999", "raw": { "any": "data" } },
      "created_at": "2026-01-31T20:21:00Z"
    }
  ]
}
```

### 3) PDF indir
`GET /api/v1/einvoices/{einvoice}/pdf`

**Örnek**
```bash
curl -L -o "einvoice-123.pdf" \
  -H "Authorization: Bearer $API_TOKEN" \
  "$BASE_URL/api/v1/einvoices/123/pdf"
```

Not: PDF endpoint’i `local` disk üzerinde bulunan dosyayı download eder. PDF yoksa `404` döner.

### 4) Provider Status güncelle
`POST /api/v1/einvoices/{einvoice}/provider-status`

**Yetki**: `einvoices:status` zorunlu.

**Body**
- `provider_status` (string, zorunlu)
- `provider_invoice_id` (string, opsiyonel)
- `raw` (object/array, opsiyonel; provider’dan gelen ekstra alanlar)

**Örnek**
```bash
curl -sS -X POST "$BASE_URL/api/v1/einvoices/123/provider-status" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "provider_status": "accepted",
    "provider_invoice_id": "PRV-999",
    "raw": { "provider_message": "OK" }
  }'
```

Bu endpoint:
- `e_invoices.provider_status` / `provider_invoice_id` alanlarını günceller
- `e_invoice_events` tablosuna `provider_status_updated` türünde event ekler
- Güncellenmiş faturayı JSON olarak döner

## Hata kodları ve örnek formatlar

### 401 Unauthenticated
```json
{ "message": "Unauthenticated." }
```

### 403 Forbidden
Örnek: `provider-status` için `einvoices:status` yetkisi yok.
```json
{ "message": "This action is unauthorized." }
```

### 404 Not Found
Örnek: Fatura başka kullanıcıya ait veya PDF yok.
```json
{ "message": "Not Found." }
```

### 422 Validation Error
```json
{
  "message": "The provider status field is required.",
  "errors": {
    "provider_status": ["The provider status field is required."]
  }
}
```

### 500 Server Error
```json
{ "message": "Server Error" }
```

## Webhooks
Webhook altyapısı, e-fatura event’lerini sizin sisteminize **HTTP POST** ile gönderir.

### Modül / Lisans
Webhook endpoint oluşturma, aktif etme ve delivery (teslimat) işlemleri `module:feature.einvoice_webhooks` ile korunur.

### Event listesi
- `einvoice.created`
- `einvoice.issued`
- `einvoice.sent`
- `einvoice.status_changed`
- `einvoice.cancelled`
- `einvoice.return_created`
- `einvoice.credit_note_created`
- Wildcard: `einvoice.*`
- Test: `webhook.test`

### Signature (HMAC-SHA256)
Her webhook isteğinde şu header’lar gönderilir:
- `X-Webhook-Event`
- `X-Webhook-Id` (uuid)
- `X-Webhook-Timestamp` (unix epoch)
- `X-Webhook-Signature` (hex HMAC-SHA256)

Signing string:
```
<timestamp>.<raw_json_body>
```

HMAC:
```
hex_hmac = HMAC_SHA256(secret, signing_string)
```

Öneri (replay koruması): Timestamp drift maksimum **5 dakika** (±300s) kabul edin.

### Retry policy
Başarısız denemelerde yeniden deneme planı:
1) 1 dk
2) 5 dk
3) 15 dk
4) 60 dk
5) 6 saat

### Payload örneği
```json
{
  "event": "einvoice.issued",
  "id": "123",
  "created_at": "2026-02-01T12:00:00Z",
  "data": {
    "einvoice": {
      "id": 456,
      "status": "issued",
      "type": "sale",
      "invoice_no": "EA-2026-000456",
      "issued_at": "2026-02-01T12:00:00Z",
      "totals": { "subtotal": 100, "tax_total": 20, "discount_total": 0, "grand_total": 120, "currency": "TRY" },
      "marketplace": "trendyol",
      "order_no": "TY-ORDER-001",
      "provider": "custom",
      "provider_status": "queued"
    },
    "user": { "id": 2 }
  }
}
```

## Güvenlik notları
- Token **bir kere gösterilir**; kaybederseniz yeni token üretin.
- Token’lar **süreli** oluşturulur (ör. 30/90/180/365 gün). Süresi dolan token otomatik olarak geçersiz olur.
- İsterseniz token’a **IP kısıtı (allowlist)** tanımlayabilirsiniz (IP veya CIDR).
- Token’ı client-side (browser/mobile) içine gömmeyin; mümkünse sunucu tarafında saklayın.
- Token sızdıysa ilgili token’ı admin panelden iptal edin.
- Yalnızca gerekli yetkileri verin (least privilege): çoğu kullanım için `einvoices:read` yeterlidir; `einvoices:status` sadece provider callback için.
