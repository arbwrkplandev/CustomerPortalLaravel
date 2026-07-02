# WrkPlan .NET API Setup Guide

This guide maps the current Laravel API contract to a future .NET implementation.

## 1) Target Architecture

- Frontend and web clients call endpoints under /api/v1.
- Authentication and session payload contract remain stable.
- .NET can replace Laravel endpoint-by-endpoint while preserving request and response shape.

## 2) Standard Response Envelope

All endpoints should return:

```json
{
  "success": true,
  "message": "Success",
  "data": {}
}
```

Validation error shape:

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["message"]
  }
}
```

## 3) Authentication Contract

Login supports customer-scoped credentials:

```json
POST /api/v1/auth/login
{
  "login": "john.smith",
  "password": "StrongPass123",
  "corp_id": "ACME-IND",
  "remember": false
}
```

Standard session payload:

```json
{
  "user_id": 1,
  "tenant_id": 10,
  "role": "customer",
  "display_name": "John Doe",
  "email": "john@acme.com",
  "session_token": "...",
  "expires_at": "2026-07-03T12:00:00Z",
  "permissions": ["portal.view"]
}
```

## 4) Recommended .NET Delivery Order

1. Auth endpoints
2. Admin dashboard endpoint
3. Tenant endpoints (create, update, toggle, assign subscription)
4. Plan endpoints
5. Invoice and contract endpoints
6. Ticket and announcement endpoints
7. Audit endpoint
8. Customer endpoints

## 5) Tenant and Pricing Rules

- Tenants must have corp_id.
- Customer login identity is username or email, optionally scoped by corp_id.
- Subscriptions keep both values:
  - base_amount: default rate from selected plan and billing cycle
  - amount: charged rate (may be custom/special)
- If amount != base_amount, set is_custom_rate = true.

## 6) Compatibility Notes for SQL Server

- MySQL enum columns can be represented as NVARCHAR with check constraints.
- JSON columns can map to NVARCHAR(MAX) with JSON validation or SQL Server JSON functions.
- Keep identifiers and foreign keys aligned with existing names to reduce migration risk.

## 7) Smoke Test Checklist

- Login with admin user works.
- Login with customer corp_id + username works.
- Create tenant with initial plan and custom rate.
- Assign a new subscription with custom rate.
- Customer dashboard and subscription endpoints show amount, base_amount, and is_custom_rate correctly.
- Audit endpoint returns pagination envelope.
