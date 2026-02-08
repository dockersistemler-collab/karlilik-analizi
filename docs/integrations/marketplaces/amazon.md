# Amazon Entegrasyon Notları

Bu doküman, Amazon SP-API entegrasyonu için gerekli endpoint ve mapping checklist'ini özetler.
Gerçek API çağrıları bu dokümandaki alanlara göre adapter içinde doldurulacaktır.

## Kimlik Doğrulama
- OAuth / LWA token + AWS SigV4 (SP-API)
- Base URL: bölgeye göre değişir (ör: `https://sellingpartnerapi-eu.amazon.com`)

Credentials JSON (örnek):
```json
{
  "access_token": "LWA_TOKEN",
  "base_url": "https://sellingpartnerapi-eu.amazon.com"
}
```

## Siparişler (Orders)
- Endpoint: `/orders`
- Filtreler:
  - `createdAfter`
  - `createdBefore`
- Mapping checklist:
  - `order_id`
  - `order_item_id`
  - `order_date`
  - `ship_date`
  - `delivered_date`
  - `sku`
  - `quantity`
  - `currency`
  - `gross_sales`
  - `discounts`
  - `refunds`
  - `commission_fee`
  - `payment_fee`
  - `shipping_fee`
  - `other_fees`
  - `vat_amount` / `tax_amount`
  - `status`

## İadeler (Returns)
- Endpoint: `/returns`
- Filtreler:
  - `createdAfter`
  - `createdBefore`
- Mapping checklist:
  - `order_item_id`
  - `refund_amount`
  - `currency`
  - `occurred_at`

## Ücretler / Komisyonlar (Fees)
- Endpoint: `/fees`
- Filtreler:
  - `startDate`
  - `endDate`
- Mapping checklist:
  - `order_item_id`
  - `fee_type`
  - `amount`
  - `currency`
  - `occurred_at`

## TODO
- SP-API auth flow detaylandırılmalı (LWA refresh + SigV4).
- Gerçek response schema ve endpoint yolları dokümana eklenecek.
- Mapping kesinleştikten sonra adapter içindeki `map*` metotları finalize edilmeli.
