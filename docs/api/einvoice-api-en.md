# E-Invoice API (v1) — Customer / Integrator Guide (EN)

This document helps you start using the **E‑Invoice API** in Pazaryeri Entegrasyon quickly from external systems (ERP, accounting software, integrators).

## What does the API do?
- Lists your **E‑Invoice** records and returns invoice details.
- Lets you download the **PDF** output for an invoice.
- Allows a provider/integrator to write back status updates via **provider-status** (creates an event log entry).

## Who should use it?
- Developers integrating ERP/accounting systems
- E‑Invoice providers / integrators
- Technical teams building internal automations

## Important: API requires the module purchase
All API routes are protected by:
- `auth:sanctum` (Bearer token)
- `module:feature.einvoice_api` (API will not work unless this module is active)

You must have **feature.einvoice_api (yearly)** enabled to use the API.

## Versioning
- Base path: `/api/v1`

## Creating a token (Admin Panel)
1) Open `admin/settings/api` in the admin panel.
2) Enter a token name and select abilities.
3) The token is shown **only once**. Store it securely.

### Abilities
- `einvoices:read` → List/Detail/PDF read access
- `einvoices:status` → Use the `provider-status` endpoint

Note: `provider-status` requires `einvoices:status`.

## Authentication (Bearer Token)
Send a Bearer token on every request:

```bash
Authorization: Bearer <API_TOKEN>
Accept: application/json
```

Example (masked token):
```bash
export BASE_URL="http://localhost"
export API_TOKEN="eyJ0eXAiOiJKV1QiLCJhbGciOiJ...REDACTED"
```

## First request: List invoices
`GET /api/v1/einvoices`

```bash
curl -sS "$BASE_URL/api/v1/einvoices" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

## Endpoint list

### 1) List einvoices
`GET /api/v1/einvoices`

**Query parameters**
- `status` (string, optional)
- `marketplace` (string, optional)
- `type` (string, optional)
- `updated_since` (datetime string, optional; ISO‑8601 recommended)
- `per_page` (int, optional; 1–100; default 20)
- `page` (int, optional)

**Example**
```bash
curl -sS "$BASE_URL/api/v1/einvoices?status=issued&per_page=20&page=1" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

### 2) Get invoice detail
`GET /api/v1/einvoices/{einvoice}`

**Query parameters**
- `include_items` (bool, optional; default `true`)
- `include_events` (bool, optional; default `false`)

**Example**
```bash
curl -sS "$BASE_URL/api/v1/einvoices/123?include_items=1&include_events=1" \
  -H "Authorization: Bearer $API_TOKEN" \
  -H "Accept: application/json"
```

### 3) Download PDF
`GET /api/v1/einvoices/{einvoice}/pdf`

```bash
curl -L -o "einvoice-123.pdf" \
  -H "Authorization: Bearer $API_TOKEN" \
  "$BASE_URL/api/v1/einvoices/123/pdf"
```

Note: The PDF endpoint downloads a file from the `local` storage disk. If the PDF does not exist, it returns `404`.

### 4) Update provider status
`POST /api/v1/einvoices/{einvoice}/provider-status`

**Ability**: requires `einvoices:status`.

**Body**
- `provider_status` (string, required)
- `provider_invoice_id` (string, optional)
- `raw` (object/array, optional; extra provider payload)

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

This endpoint:
- updates `e_invoices.provider_status` / `provider_invoice_id`
- inserts an `e_invoice_events` row with type `provider_status_updated`
- returns the updated invoice JSON

## Error codes (typical formats)

### 401 Unauthenticated
```json
{ "message": "Unauthenticated." }
```

### 403 Forbidden
Example: calling `provider-status` without `einvoices:status`.
```json
{ "message": "This action is unauthorized." }
```

### 404 Not Found
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
The webhook subsystem sends e-invoice events to your system via **HTTP POST**.

### Module / License
Creating/enabling webhook endpoints and executing deliveries are protected by `module:feature.einvoice_webhooks`.

### Events
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
Each webhook request includes:
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

Replay guidance: reject requests with timestamp drift greater than **5 minutes** (±300s).

### Retry policy
Backoff schedule on failures:
1) 1 min
2) 5 min
3) 15 min
4) 60 min
5) 6 hours

### Sample payload
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

## Security notes
- The token is displayed **once**; if you lose it, create a new one.
- Tokens are created with an **expiration** (e.g. 30/90/180/365 days). Expired tokens become invalid automatically.
- You can optionally configure an **IP allowlist** (IP or CIDR) per token.
- Do not embed the token in client apps; store it server-side when possible.
- If leaked, revoke the token from the admin panel immediately.
- Follow least-privilege: `einvoices:read` is enough for most use cases; use `einvoices:status` only for provider callbacks.
