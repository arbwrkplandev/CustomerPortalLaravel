# WrkPlan REST API Documentation

Base URL: `/api/v1/`  
Auth: Session cookie `wrkplan_session` (set on login)  
Swagger UI: `http://localhost:8000/api/documentation`

---

## Authentication

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/auth/login` | Authenticate and receive session payload |
| POST | `/api/v1/auth/logout` | Invalidate session |
| GET  | `/api/v1/auth/me` | Get current user session payload |

### Login Request
```json
POST /api/v1/auth/login
{
  "email": "admin@wrkplan.com",
  "password": "password",
  "remember": false
}
```

### Session Payload (standardized contract)
```json
{
  "user_id": 1,
  "tenant_id": null,
  "role": "admin",
  "display_name": "Alex Admin",
  "email": "admin@wrkplan.com",
  "session_token": "...",
  "expires_at": "2025-01-01T12:00:00Z",
  "permissions": ["tenants.*", "contracts.*", "invoices.*", ...]
}
```

---

## Admin Endpoints

All admin routes require `role: admin|superadmin`.

### Tenants
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/admin/tenants` | List all tenants (paginated) |
| POST | `/api/v1/admin/tenants` | Create new tenant |
| GET  | `/api/v1/admin/tenants/{id}` | Tenant details |
| PUT  | `/api/v1/admin/tenants/{id}` | Update tenant |
| PATCH | `/api/v1/admin/tenants/{id}/toggle-status` | Activate/deactivate |
| POST | `/api/v1/admin/tenants/{id}/subscription` | Assign subscription plan |

### Contracts
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/admin/contracts` | List all contracts |
| POST | `/api/v1/admin/contracts` | Create contract |
| GET  | `/api/v1/admin/contracts/{id}` | Contract details |
| POST | `/api/v1/admin/contracts/{id}/send` | Send to customer for signing |
| GET  | `/api/v1/admin/contracts/{id}/download` | Download PDF |

### Invoices
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/admin/invoices` | List invoices |
| POST | `/api/v1/admin/invoices` | Create invoice |
| GET  | `/api/v1/admin/invoices/{id}` | Invoice detail |
| POST | `/api/v1/admin/invoices/{id}/payment` | Record payment |
| GET  | `/api/v1/admin/invoices/{id}/pdf` | Download PDF |

### Support Tickets
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/admin/tickets` | List tickets (with filters) |
| GET  | `/api/v1/admin/tickets/{id}` | Ticket thread |
| POST | `/api/v1/admin/tickets/{id}/reply` | Reply to customer |
| PATCH | `/api/v1/admin/tickets/{id}/status` | Update ticket status |
| PATCH | `/api/v1/admin/tickets/{id}/assign` | Assign to admin |

### Announcements
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/admin/announcements` | List announcements |
| POST | `/api/v1/admin/announcements` | Create announcement |
| PUT  | `/api/v1/admin/announcements/{id}` | Update announcement |
| PATCH | `/api/v1/admin/announcements/{id}/toggle` | Publish/unpublish |
| DELETE | `/api/v1/admin/announcements/{id}` | Delete announcement |

---

## Customer Endpoints

All customer routes require `role: customer`. Responses are automatically scoped to `tenant_id`.

### Dashboard
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/customer/dashboard` | Summary: subscription, invoices, tickets, announcements |

### Contracts
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/customer/contracts` | My contracts |
| GET  | `/api/v1/customer/contracts/{id}` | Contract detail |
| POST | `/api/v1/customer/contracts/{id}/sign` | E-sign (Base64 signature image) |
| POST | `/api/v1/customer/contracts/{id}/upload` | Upload signed copy |
| GET  | `/api/v1/customer/contracts/{id}/download` | Download PDF |

### E-Sign Request
```json
POST /api/v1/customer/contracts/{id}/sign
{
  "signature_image": "data:image/png;base64,iVBORw0KGgo...",
  "agreed": true
}
```

### Support Tickets
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/customer/tickets` | My tickets |
| POST | `/api/v1/customer/tickets` | Create new ticket |
| GET  | `/api/v1/customer/tickets/{id}` | Ticket thread |
| POST | `/api/v1/customer/tickets/{id}/reply` | Add reply |

### Subscriptions & Invoices
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/v1/customer/subscription` | Active subscription |
| GET  | `/api/v1/customer/plans` | Available plans |
| GET  | `/api/v1/customer/invoices` | My invoices |
| GET  | `/api/v1/customer/invoices/{id}` | Invoice detail |
| GET  | `/api/v1/customer/invoices/{id}/download` | Download PDF |

---

## Health Check

```
GET /api/v1/health
→ 200 { "status": "ok", "timestamp": "..." }
```

---

## Error Response Format

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error"]
  }
}
```

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | Success |
| 201 | Created |
| 401 | Unauthenticated |
| 403 | Forbidden (wrong role) |
| 404 | Not found |
| 422 | Validation error |
| 500 | Server error |
