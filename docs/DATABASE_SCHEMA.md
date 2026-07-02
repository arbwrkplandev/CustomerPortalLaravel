# WrkPlan Database Schema (Current)

Engine target: MySQL 8 (current), easy to map to SQL Server for .NET.

## High-Level Relationships

- tenants 1-to-many users
- tenants 1-to-many customer_subscriptions
- plans 1-to-many customer_subscriptions
- tenants 1-to-many invoices
- invoices 1-to-many payments
- tenants 1-to-many contracts
- contracts 1-to-many contract_sign_fields
- contracts 1-to-many contract_files
- tenants 1-to-many support_tickets
- support_tickets 1-to-many support_ticket_messages
- announcements global or tenant-targeted
- audit_logs captures major writes
- auth_session_map stores provider-neutral session payload

## Core Auth and Tenant Tables

### tenants

Important fields:
- id
- company_name
- corp_id (customer login scope key)
- contact_name
- contact_email
- contact_phone
- city
- country
- timezone
- status
- trial_ends_at
- settings (json)
- deleted_at

### users

Important fields:
- id
- tenant_id (nullable for admin users)
- name
- username (customer login key)
- email
- role (superadmin, admin, customer)
- password
- plain_password (legacy migration helper)
- is_active
- deleted_at

## Plan and Subscription Tables

### plans

Important fields:
- id
- name
- slug
- description
- monthly_price
- quarterly_price
- annual_price
- features (json)
- max_users
- is_active
- sort_order

### customer_subscriptions

Important fields:
- id
- tenant_id
- plan_id
- billing_cycle (monthly, quarterly, annual)
- status (active, expired, cancelled, pending)
- start_date
- end_date
- next_renewal_date
- amount (actual charged rate)
- base_amount (default plan rate)
- is_custom_rate (true for special pricing)
- currency
- notes
- created_by

Pricing rule:
- If custom rate provided, amount = custom rate and is_custom_rate = true.
- base_amount always stores the plan-default rate for selected billing cycle.

## Billing Tables

### invoices

Important fields:
- id
- tenant_id
- subscription_id
- invoice_number
- status
- subtotal
- tax_amount
- total_amount
- due_date
- paid_at
- line_items (json)
- created_by

### payments

Important fields:
- id
- invoice_id
- tenant_id
- amount
- currency
- method
- status
- payment_date
- notes

## Contract Tables

### contracts

Important fields:
- id
- tenant_id
- title
- content
- status
- sent_at
- signed_at
- signed_by
- signature_image
- created_by

### contract_sign_fields

Important fields:
- id
- contract_id
- field_type
- page_number
- x_position
- y_position
- width
- height
- is_required
- value
- filled_at

### contract_files

Important fields:
- id
- contract_id
- tenant_id
- file_type
- file_path
- file_name
- mime_type
- file_size
- uploaded_by

## Support Tables

### support_tickets

Important fields:
- id
- tenant_id
- created_by
- assigned_to
- ticket_number
- subject
- description
- category
- priority
- status
- resolved_at

### support_ticket_messages

Important fields:
- id
- ticket_id
- user_id
- message
- is_internal
- attachments (json)

## Platform Communication and Audit

### announcements

Important fields:
- id
- title
- content
- type
- priority
- is_published
- published_at
- expires_at
- target_tenants (json)
- target_roles (json)
- created_by

### audit_logs

Important fields:
- id
- user_id
- tenant_id
- action
- module
- entity_id
- entity_type
- old_values (json)
- new_values (json)
- ip_address
- user_agent
- description

### auth_session_map

Important fields:
- id
- user_id
- session_token
- provider
- payload (json)
- ip_address
- user_agent
- expires_at

## .NET Mapping Notes

- Keep table and column names unchanged for first migration.
- Map enum-like values to constrained strings in SQL Server.
- Use decimal(10,2) equivalent for monetary fields.
- Preserve json fields as nvarchar(max) with JSON constraints or typed projections.
- Preserve subscription pricing triad: amount, base_amount, is_custom_rate.
