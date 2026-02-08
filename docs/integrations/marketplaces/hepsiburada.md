# Hepsiburada Entegrasyon Notları

Bu doküman, Hepsiburada API entegrasyonu için gerekli endpoint ve mapping checklist'ini özetler.
Gerçek API çağrıları bu dokümandaki alanlara göre adapter içinde doldurulacaktır.

## Kimlik Doğrulama
- Basic Auth veya Token (partner sözleşmesine göre değişir)
- Base URL: Hepsiburada partner API base (dokümantasyona göre netleştirilecek)

Credentials JSON (örnek):
```json
{
  "api_key": "xxxx",
  "api_secret": "yyyy",
  "base_url": "https://api.hepsiburada.com/..."
}
```

## Siparişler (Orders)
- Endpoint: `/orders`
- Filtreler:
  - `startDate`
  - `endDate`
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
  - `startDate`
  - `endDate`
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
- Gerçek response schema ve endpoint yolları dokümana eklenecek.
- Mapping kesinleştikten sonra adapter içindeki `map*` metotları finalize edilmeli.
