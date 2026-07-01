# WrkPlan Database Schema

Database: `wrkplan_db`  
Engine: MySQL 8+ via XAMPP  
Connection: Unix socket `/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock`

---

## Entity Relationship Overview

```
tenants ──< users
tenants ──< customer_subscriptions >── plans
tenants ──< invoices >── payments
tenants ──< contracts >── contract_sign_fields
                      └── contract_files
tenants ──< support_tickets >── support_ticket_messages
announcements (global or tenant-targeted)
audit_logs (all write operations)
auth_session_map (provider-agnostic session tracking)
```

---

## Tables

### `users`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK→tenants | NULL for admin users |
| name | varchar(255) | |
| email | varchar(255) UNIQUE | |
| password | varchar(255) | bcrypt hashed |
| plain_password | varchar(255) NULL | For .NET migration only |
| role | enum | `superadmin`, `admin`, `customer` |
| is_active | boolean | Default true |
| avatar | varchar NULL | Path to avatar file |
| preferred_theme | varchar | `light`, `dark`, `system` |
| preferred_color | varchar | Hex color for custom theme |
| email_verified_at | timestamp NULL | |
| deleted_at | timestamp NULL | Soft delete |

### `tenants`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| company_name | varchar(255) | |
| slug | varchar UNIQUE | URL-friendly identifier |
| contact_name | varchar(255) | Primary contact person |
| contact_email | varchar UNIQUE | |
| contact_phone | varchar(30) NULL | |
| address | text NULL | |
| city | varchar(100) NULL | |
| country | varchar(100) NULL | |
| timezone | varchar(60) | Default: UTC |
| logo | varchar NULL | Path to logo file |
| status | enum | `active`, `inactive`, `suspended`, `trial` |
| trial_ends_at | timestamp NULL | |
| settings | JSON NULL | Arbitrary tenant settings |
| deleted_at | timestamp NULL | Soft delete |

### `plans`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | varchar(255) | e.g., "Professional" |
| slug | varchar UNIQUE | |
| description | text NULL | |
| price_monthly | decimal(10,2) | |
| price_quarterly | decimal(10,2) | |
| price_annual | decimal(10,2) | |
| features | JSON | Array of feature strings |
| max_users | int NULL | NULL = unlimited |
| max_storage_gb | int NULL | NULL = unlimited |
| is_active | boolean | |
| sort_order | int | Display order |

### `customer_subscriptions`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK→tenants | |
| plan_id | bigint FK→plans | |
| status | enum | `trial`, `active`, `cancelled`, `expired`, `paused` |
| billing_cycle | enum | `monthly`, `quarterly`, `annual` |
| price_paid | decimal(10,2) | Actual price at time of subscription |
| starts_at | timestamp | |
| ends_at | timestamp NULL | NULL = perpetual |
| cancelled_at | timestamp NULL | |
| notes | text NULL | |
| created_by | bigint FK→users NULL | Admin who created it |

### `invoices`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK→tenants | |
| subscription_id | bigint FK→customer_subscriptions NULL | |
| invoice_number | varchar UNIQUE | Auto-generated: INV-YYYYMM-XXXX |
| status | enum | `draft`, `pending`, `paid`, `overdue`, `cancelled` |
| subtotal | decimal(10,2) | |
| tax_amount | decimal(10,2) | Default 0 |
| total_amount | decimal(10,2) | |
| due_date | date NULL | |
| paid_at | timestamp NULL | |
| notes | text NULL | |
| line_items | JSON NULL | Array of line item objects |
| created_by | bigint FK→users NULL | |

### `payments`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| invoice_id | bigint FK→invoices | |
| tenant_id | bigint FK→tenants | |
| amount | decimal(10,2) | |
| currency | varchar(3) | Default: USD |
| method | enum | `bank_transfer`, `credit_card`, `cheque`, `cash`, `other` |
| status | enum | `pending`, `completed`, `failed`, `refunded` |
| gateway | varchar NULL | Payment gateway name |
| gateway_reference | varchar NULL | External transaction ID |
| gateway_response | JSON NULL | Full gateway response |
| notes | text NULL | |
| paid_at | timestamp NULL | |
| recorded_by | bigint FK→users NULL | Admin who recorded payment |

### `contracts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK→tenants | |
| title | varchar(255) | |
| description | text NULL | |
| content | longtext | Full contract text |
| status | enum | `draft`, `sent`, `signed`, `cancelled`, `expired` |
| valid_from | date NULL | |
| valid_until | date NULL | |
| sent_at | timestamp NULL | When sent to customer |
| signed_at | timestamp NULL | When customer signed |
| signed_by | bigint FK→users NULL | Customer who signed |
| signature_image | longtext NULL | Base64 PNG of signature |
| ip_address | varchar NULL | Signing IP for audit |
| created_by | bigint FK→users NULL | Admin who created |
| deleted_at | timestamp NULL | Soft delete |

### `contract_sign_fields`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| contract_id | bigint FK→contracts | |
| field_type | enum | `signature`, `initials`, `date`, `text` |
| page_number | int | |
| x_position | decimal(8,2) | Percentage from left |
| y_position | decimal(8,2) | Percentage from top |
| width | decimal(8,2) | |
| height | decimal(8,2) | |
| is_required | boolean | |
| value | text NULL | Filled value |
| filled_at | timestamp NULL | |

### `contract_files`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| contract_id | bigint FK→contracts | |
| file_type | enum | `original`, `signed`, `amendment`, `attachment` |
| file_path | varchar(255) | Storage path |
| file_name | varchar(255) | Display name |
| mime_type | varchar(100) | |
| file_size | int | Bytes |
| uploaded_by | bigint FK→users NULL | |

### `support_tickets`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint FK→tenants | |
| created_by | bigint FK→users | Customer who opened ticket |
| assigned_to | bigint FK→users NULL | Admin assigned |
| subject | varchar(255) | |
| category | enum | `general`, `billing`, `technical`, `contract`, `other` |
| priority | enum | `low`, `medium`, `high`, `critical` |
| status | enum | `open`, `in_progress`, `waiting_customer`, `resolved`, `closed` |
| resolved_at | timestamp NULL | |
| deleted_at | timestamp NULL | Soft delete |

### `support_ticket_messages`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| ticket_id | bigint FK→support_tickets | |
| sender_id | bigint FK→users | |
| sender_type | enum | `customer`, `admin` |
| message | text | |
| is_internal | boolean | Internal notes not visible to customer |
| attachments | JSON NULL | File references |

### `announcements`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| created_by | bigint FK→users | |
| title | varchar(255) | |
| content | text | |
| type | enum | `info`, `warning`, `success`, `maintenance`, `feature` |
| priority | enum | `low`, `medium`, `high`, `critical` |
| is_published | boolean | |
| published_at | timestamp NULL | |
| expires_at | timestamp NULL | |
| target_tenants | JSON NULL | Array of tenant IDs, NULL = all |
| target_roles | JSON NULL | Array of roles, NULL = all |
| deleted_at | timestamp NULL | Soft delete |

### `audit_logs`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| tenant_id | bigint NULL | NULL for admin actions |
| user_id | bigint FK→users NULL | NULL for system actions |
| action | varchar(50) | e.g., `create`, `update`, `delete`, `login` |
| auditable_type | varchar NULL | Model class name |
| auditable_id | bigint NULL | Model ID |
| description | text NULL | Human-readable description |
| old_values | JSON NULL | State before change |
| new_values | JSON NULL | State after change |
| ip_address | varchar(45) NULL | |
| user_agent | text NULL | |
| created_at | timestamp | Only created_at (no updated_at) |

### `auth_session_map`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| user_id | bigint FK→users | |
| session_token | varchar(64) | Unique session token |
| provider | varchar(20) | `laravel` or `dotnet` |
| payload | JSON | Full standardized session payload |
| ip_address | varchar(45) NULL | |
| user_agent | text NULL | |
| expires_at | timestamp | |
| created_at / updated_at | timestamps | |

---

## Demo Data (Seeded)

| Role | Email | Password |
|------|-------|----------|
| superadmin | superadmin@wrkplan.com | password |
| admin | admin@wrkplan.com | password |
| customer | john@acme.com | password |
| customer | sarah@techstart.io | password |

Tenants: Acme Corp, TechStart Inc, Global Ventures, Sunrise Media  
Plans: Starter ($49/mo), Professional ($149/mo), Enterprise ($499/mo)
