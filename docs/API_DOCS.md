# WrkPlan API Documentation (v1)

Base URL: /api/v1

Auth mode: hybrid.

- Web portal traffic can continue using Laravel session auth through the internal gateway.
- Third-party API clients should authenticate with `POST /auth/login` and send the returned `session_token` as either `Authorization: Bearer <token>` or `X-Session-Token: <token>`.
- Auth provider remains swappable to .NET through the existing auth abstraction.

## Interactive Developer Portals

- API portal UI: /docs/api
- Raw OpenAPI spec: /docs/openapi.yaml
- Swagger UI (legacy route): /api/documentation
- Database visual docs: /docs/database
- Database schema JSON feed: /docs/database/schema.json

These routes are protected by `auth` + `admin.only` middleware.

## Response Contract

Successful response:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Paginated response:

```json
{
  "success": true,
  "message": "Success",
  "data": [],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 50,
    "from": 1,
    "to": 20
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

Error response:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["message"]
  }
}
```

## Authentication Endpoints

- POST /auth/login
- POST /auth/logout
- GET /auth/me

Direct-client auth contract:
- `POST /auth/login` returns `data.session_token`
- send that token as `Authorization: Bearer <session_token>` or `X-Session-Token: <session_token>`
- `POST /auth/logout` revokes the specific token used for the request
- `GET /auth/me` returns the normalized current-user payload for either a direct API token or a web-authenticated session

Login payload (web + API compatible):

```json
{
  "login": "john.smith",
  "email": "john@acme.com",
  "password": "StrongPass123",
  "corp_id": "ACME-IND",
  "remember": false
}
```

Notes:
- login or email can be used as identity.
- corp_id is optional for admin users and required for customer-scoped login.
- Successful login returns `data.session_token` and `data.expires_at` for direct API use.

Bearer example:

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "login": "sarah@techstart.io",
    "password": "password",
    "corp_id": "TECHSTAR-0002"
  }'
```

Then use the returned token:

```bash
curl http://localhost:8000/api/v1/customer/dashboard \
  -H "Accept: application/json" \
  -H "Authorization: Bearer <session_token>"
```

Alternative header:

```bash
curl http://localhost:8000/api/v1/customer/dashboard \
  -H "Accept: application/json" \
  -H "X-Session-Token: <session_token>"
```

## Admin Endpoints

All endpoints below require an authenticated admin or superadmin token.

### Dashboard

- GET /admin/dashboard

Returns:
- stats summary
- recent_tenants with active subscription plan
- recent_tickets

### Tenants

- GET /admin/tenants
- POST /admin/tenants
- GET /admin/tenants/{tenant}
- PUT /admin/tenants/{tenant}
- POST /admin/tenants/{tenant}/toggle-status
- POST /admin/tenants/{tenant}/assign-subscription
- **PATCH /admin/tenants/{tenant}/subscription** ← _Update existing subscription_
- POST /admin/tenants/{tenant}/users/{user}/reset-password

**GET /admin/tenants** — Response includes `active_subscription` with nested `plan` object for every tenant (used in list view to show plan badges).

Assign subscription payload (POST — replaces any existing subscription):

```json
{
  "plan_id": 2,
  "billing_cycle": "monthly",
  "start_date": "2026-07-02",
  "custom_rate": 129.00,
  "currency": "USD",
  "notes": "Special partner pricing"
}
```

**PATCH /admin/tenants/{tenant}/subscription** — Updates the current active subscription:
- If `plan_id` or `billing_cycle` changes → cancels old subscription and creates a new one (same as assign-subscription)
- If only `custom_rate` changes → updates the amount in-place without cycling the subscription period
- Set `custom_rate: null` to revert to the plan's standard price

```json
{
  "plan_id": 2,
  "billing_cycle": "quarterly",
  "custom_rate": 349.00,
  "currency": "USD",
  "notes": "Upgraded to quarterly with negotiated rate"
}
```

Response `200 OK`:
```json
{
  "success": true,
  "message": "Subscription updated successfully",
  "data": {
    "id": 5,
    "tenant_id": 1,
    "plan_id": 2,
    "billing_cycle": "quarterly",
    "amount": "349.00",
    "base_amount": "399.00",
    "is_custom_rate": true,
    "currency": "USD",
    "status": "active",
    "start_date": "2026-07-01",
    "end_date": "2026-10-01",
    "plan": { "id": 2, "name": "Professional", ... }
  }
}
```

Reset password payload:

```json
{
  "new_password": "NewStrongPass123",
  "new_password_confirmation": "NewStrongPass123"
}
```

### Plans

- GET /admin/plans
- POST /admin/plans
- GET /admin/plans/{plan}
- PUT /admin/plans/{plan}
- POST /admin/plans/{plan}/toggle-status

Create or update payload:

```json
{
  "name": "Professional",
  "description": "Best for growing businesses",
  "monthly_price": 149.00,
  "quarterly_price": 399.00,
  "annual_price": 1499.00,
  "max_users": 50,
  "sort_order": 20,
  "is_active": true,
  "features": ["Priority Support", "Advanced Reports"]
}
```

### Contracts

- GET /admin/contracts
- POST /admin/contracts
- GET /admin/contracts/{contract}
- POST /admin/contracts/{contract}/send
- POST /admin/contracts/{contract}/revoke
- GET /admin/contracts/{contract}/download/{type?}
- GET /admin/contracts/{contract}/stream/{type?}

Notes:
- `type` can be `original` or `signed`
- if the original PDF is unavailable but a signed PDF exists, file delivery falls back to the signed copy instead of returning a false 404

### Invoices and Payments

- GET /admin/invoices
- POST /admin/invoices
- GET /admin/invoices/{invoice}
- POST /admin/invoices/{invoice}/payment
- GET /admin/invoices/{invoice}/download-pdf

Notes:
- web admin invoice downloads are proxied through the internal gateway and no longer redirect the browser to raw `/api/v1` URLs

### Tickets

- GET /admin/tickets
- GET /admin/tickets/{supportTicket}
- POST /admin/tickets/{supportTicket}/reply
- PATCH /admin/tickets/{supportTicket}/status
- PATCH /admin/tickets/{supportTicket}/assign

### Announcements

- GET /admin/announcements
- POST /admin/announcements
- PUT /admin/announcements/{announcement}
- POST /admin/announcements/{announcement}/toggle-publish
- DELETE /admin/announcements/{announcement}

### Audit

- GET /admin/audit

Optional query params: search, module, date_from, date_to, per_page.

## Customer Endpoints

All endpoints below require an authenticated customer token.

All endpoints below are tenant-scoped.

### Dashboard

- GET /customer/dashboard

### Subscription and Invoices

- GET /customer/subscription
- GET /customer/plans
- GET /customer/invoices
- GET /customer/invoices/{id}
- GET /customer/invoices/{id}/download
- GET /customer/renewal-preview

Notes:
- customer portal invoice downloads are proxied through the internal gateway and return PDF attachments correctly in the browser

### Contracts

- GET /customer/contracts
- GET /customer/contracts/{id}
- POST /customer/contracts/{id}/sign
- POST /customer/contracts/{id}/upload-signed
- GET /customer/contracts/{id}/download/{type?}
- GET /customer/contracts/{id}/stream/{type?}

Sign payload:

```json
{
  "signer_name": "Sarah Chen",
  "signature_data": "data:image/png;base64,...",
  "fields": {
    "12": "data:image/png;base64,...",
    "13": "Initials"
  }
}
```

Upload signed copy:
- multipart/form-data with file field `signed_pdf`

Notes:
- `type` can be `original` or `signed`
- if the original PDF is missing but a signed copy exists, download and stream requests fall back to the signed copy

### Tickets

- GET /customer/tickets
- POST /customer/tickets
- GET /customer/tickets/{id}
- POST /customer/tickets/{id}/reply

## Pricing Data Contract

Subscriptions keep both rate values:

- base_amount: default plan amount for selected billing_cycle.
- amount: actual charged rate.
- is_custom_rate: true when amount differs from base_amount.
- currency: charged currency code.

This contract should be preserved in .NET implementation.

## Health

- GET /health

## Request Examples

### cURL

```bash
curl -X GET "http://localhost:8000/api/v1/admin/dashboard" \
  -H "Accept: application/json" \
  -H "Cookie: wrkplan_session=<session-cookie>"
```

### JavaScript (Axios)

```javascript
import axios from 'axios';

const client = axios.create({
  baseURL: '/api/v1',
  headers: { Accept: 'application/json' },
  withCredentials: true,
});

const { data } = await client.get('/customer/dashboard');
console.log(data);
```

### JavaScript (Fetch)

```javascript
const response = await fetch('/api/v1/customer/contracts/2/sign', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
  },
  body: JSON.stringify({
    signer_name: 'Sarah Chen',
    signature_data: 'data:image/png;base64,...',
    fields: {},
  }),
});

const payload = await response.json();
console.log(payload);
```
