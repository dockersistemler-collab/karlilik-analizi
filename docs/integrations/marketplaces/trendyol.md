# Trendyol Entegrasyon Notları

Bu doküman, Trendyol API entegrasyonu için gerekli endpoint ve mapping checklist'ini özetler.
Gerçek API çağrıları bu dokümandaki alanlara göre adapter içinde doldurulacaktır.

## Kimlik Doğrulama
- Basic Auth: `api_key` + `api_secret`
- Base URL:
  - Test: `https://stageapi.trendyol.com/stage/sapigw/suppliers`
  - Prod: `https://api.trendyol.com/sapigw/suppliers`

Credentials JSON (örnek):
```json
{
  "api_key": "xxxx",
  "api_secret": "yyyy",
  "supplier_id": "12345",
  "is_test": true
}
```

## Siparişler (Orders)
- Endpoint: `/suppliers/{supplierId}/orders`
- Filtreler:
  - `startDate` (ms epoch)
  - `endDate` (ms epoch)
- Mapping checklist:
  - `order_id` (Trendyol orderNumber)
  - `order_item_id` (line item id)
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
  - `status` (paid/shipped/refunded/cancelled)

## İadeler (Returns)
- Endpoint: `/suppliers/{supplierId}/returns`
- Filtreler:
  - `startDate` (ms epoch)
  - `endDate` (ms epoch)
- Mapping checklist:
  - `order_item_id`
  - `refund_amount`
  - `currency`
  - `occurred_at`

## Ücretler / Komisyonlar (Fees/Settlements)
- Endpoint: `/suppliers/{supplierId}/settlements`
- Filtreler:
  - `startDate` (ms epoch)
  - `endDate` (ms epoch)
- Mapping checklist:
  - `order_item_id`
  - `fee_type` (commission/payment/shipping/other)
  - `amount`
  - `currency`
  - `occurred_at`

## TODO
- Gerçek response schema'ları dokümana eklenmeli.
- Mapping kesinleştikten sonra adapter içindeki `map*` metotları finalize edilmeli.
