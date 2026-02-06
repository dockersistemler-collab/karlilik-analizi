# API Docs

Bu klasör, **Pazaryeri Entegrasyon - E‑Fatura API** için dokümantasyon ve import dosyalarını içerir.

## Dosyalar
- `docs/api/einvoice-api-tr.md` — Müşteri/entegratör dokümanı (TR)
- `docs/api/einvoice-api-en.md` — Customer/integrator guide (EN)
- `docs/api/einvoice-api-openapi.yaml` — OpenAPI 3.0 (Swagger)
- `docs/api/postman/einvoice-api.postman_collection.json` — Postman collection

## Hızlı başlangıç
1) Admin panelden API modülünü satın alın / aktif edin: `feature.einvoice_api` (yıllık)
2) Admin panelden token üretin: `admin/settings/api`
3) Token’ı güvenli yerde saklayın (yalnızca bir kez gösterilir)

## OpenAPI (Swagger) kullanımı
- Swagger UI / Insomnia / Postman gibi araçlara `docs/api/einvoice-api-openapi.yaml` import edebilirsiniz.
- Server/Base URL olarak örn: `http://localhost` kullanın.

## Postman kullanımı
1) `docs/api/postman/einvoice-api.postman_collection.json` dosyasını import edin.
2) Collection variables veya Environment olarak şu değerleri set edin:
   - `BASE_URL` (örn. `http://localhost`)
   - `API_TOKEN` (Bearer token)
   - `EINVOICE_ID` (test için bir id)

## curl ile test
```bash
export BASE_URL="http://localhost"
export API_TOKEN="...REDACTED..."
curl -sS "$BASE_URL/api/v1/einvoices" -H "Authorization: Bearer $API_TOKEN" -H "Accept: application/json"
```

