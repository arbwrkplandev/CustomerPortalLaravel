# WrkPlan API Documentation (v1)

Base URL: /api/v1

Auth mode: session-based, provider swappable to .NET through auth provider abstraction.

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
- corp_id is optional for admin users and recommended for customer-scoped login.

## Admin Endpoints

All endpoints below require admin.only middleware.

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
- GET /admin/contracts/{contract}/download/{type?}

### Invoices and Payments

- GET /admin/invoices
- POST /admin/invoices
- GET /admin/invoices/{invoice}
- POST /admin/invoices/{invoice}/payment
- GET /admin/invoices/{invoice}/download-pdf

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

All endpoints below require tenant.scope middleware.

### Dashboard

- GET /customer/dashboard

### Subscription and Invoices

- GET /customer/subscription
- GET /customer/plans
- GET /customer/invoices
- GET /customer/invoices/{id}
- GET /customer/invoices/{id}/download
- GET /customer/renewal-preview

### Contracts

- GET /customer/contracts
- GET /customer/contracts/{id}
- POST /customer/contracts/{id}/sign
- POST /customer/contracts/{id}/upload-signed
- GET /customer/contracts/{id}/download/{type?}

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
