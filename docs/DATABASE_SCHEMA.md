# WrkPlan Database Schema (Current)

Engine target: MySQL/MariaDB in current environments, with the schema kept portable for SQL Server migration.

## Database Topology

Current repo behavior:
- Laravel uses one active relational database connection at a time.
- The active local runtime database in this workspace is `wrkplan_db`.
- The default Laravel connection name is `mysql`.
- Configured Laravel connection profiles are `sqlite`, `mysql`, `mariadb`, `pgsql`, and `sqlsrv`.

Environment note:
- Local `.env` points at `wrkplan_db`.
- Deployment template `.deploy.env` points at `customer_portal_wrkplan`.
- There is no second simultaneously configured application database connection named `wrkplan` in `config/database.php`.
- `WrkPlan` is the platform/application name used across the repo and docs, not a separate live Laravel database connection by itself.

## Interactive Documentation

- Visual database explorer: `/docs/database`
- Live schema JSON: `/docs/database/schema.json`

The live schema JSON is generated from the active connection and includes:
- `connection_name`
- `driver`
- `database`
- `configured_connections`
- `tables`
- `views`
- `triggers`
- `procedures`
- `foreign_keys`
- `relationships`
- `generated_at`

## Relationship Map

- `tenants` 1-to-many `users`
- `tenants` 1-to-many `customer_subscriptions`
- `plans` 1-to-many `customer_subscriptions`
- `tenants` 1-to-many `invoices`
- `invoices` 1-to-many `payments`
- `tenants` 1-to-many `contracts`
- `contracts` 1-to-many `contract_sign_fields`
- `contracts` 1-to-many `contract_files`
- `tenants` 1-to-many `support_tickets`
- `support_tickets` 1-to-many `support_ticket_messages`
- `users` 1-to-many `auth_session_map`

## Application Tables

### tenants

Purpose: tenant boundary for every customer company.

Key columns:
- `id` primary key
- `company_name`
- `corp_id` unique customer login scope key
- `slug` unique URL-safe identifier
- `contact_name`
- `contact_email` unique
- `contact_phone`
- `address`
- `city`
- `country`
- `timezone`
- `logo`
- `status` enum: `active`, `inactive`, `suspended`, `trial`
- `trial_ends_at`
- `settings` JSON tenant preferences
- `created_at`, `updated_at`, `deleted_at`

### users

Purpose: all authenticated actors across admin and customer roles.

Key columns:
- `id` primary key
- `tenant_id` nullable for admin/superadmin accounts
- `name`
- `username` unique username, used alongside email for login
- `email` unique
- `phone`
- `role` enum: `superadmin`, `admin`, `customer`
- `email_verified_at`
- `password`
- `plain_password` migration-compatibility field for non-production bridge mode
- `is_active`
- `avatar`
- `preferred_theme`
- `preferred_color`
- `remember_token`
- `created_at`, `updated_at`, `deleted_at`

### password_reset_tokens

Purpose: Laravel password reset broker storage.

Key columns:
- `email` primary key
- `token`
- `created_at`

### sessions

Purpose: Laravel session storage for web-authenticated traffic.

Key columns:
- `id` primary key
- `user_id`
- `ip_address`
- `user_agent`
- `payload`
- `last_activity`

### plans

Purpose: subscription catalog.

Key columns:
- `id` primary key
- `name`
- `slug` unique
- `description`
- `monthly_price`
- `quarterly_price`
- `annual_price`
- `features` JSON array
- `max_users`
- `is_active`
- `sort_order`
- `created_at`, `updated_at`

### customer_subscriptions

Purpose: current and historical tenant subscriptions.

Key columns:
- `id` primary key
- `tenant_id` foreign key to `tenants`
- `plan_id` foreign key to `plans`
- `billing_cycle` enum: `monthly`, `quarterly`, `annual`
- `status` enum: `active`, `expired`, `cancelled`, `pending`
- `start_date`
- `end_date`
- `next_renewal_date`
- `amount` actual billed amount
- `base_amount` standard plan amount for the selected cycle
- `is_custom_rate` boolean negotiated-pricing flag
- `currency`
- `notes`
- `created_by`
- `created_at`, `updated_at`

Pricing semantics:
- `amount` is what the tenant is actually charged.
- `base_amount` preserves the normal plan price for auditability.
- `is_custom_rate = true` means `amount` was intentionally overridden.

### invoices

Purpose: billable documents for subscriptions and manual charges.

Key columns:
- `id` primary key
- `tenant_id` foreign key to `tenants`
- `subscription_id` nullable subscription link
- `invoice_number` unique
- `status` enum: `draft`, `sent`, `paid`, `overdue`, `cancelled`
- `issue_date`
- `due_date`
- `paid_date`
- `subtotal`
- `tax_amount`
- `discount_amount`
- `total_amount`
- `currency`
- `line_items` JSON line-item array
- `notes`
- `pdf_path`
- `created_by`
- `created_at`, `updated_at`

### payments

Purpose: recorded payments against invoices or subscriptions.

Key columns:
- `id` primary key
- `tenant_id` foreign key to `tenants`
- `invoice_id` nullable foreign key to `invoices`
- `subscription_id` nullable subscription link
- `payment_reference` unique nullable external reference
- `amount`
- `currency`
- `payment_mode` enum: `online`, `bank_transfer`, `cheque`, `cash`, `manual`
- `status` enum: `pending`, `completed`, `failed`, `refunded`
- `payment_date`
- `transaction_id`
- `gateway`
- `gateway_response` JSON
- `notes`
- `recorded_by`
- `created_at`, `updated_at`

### contracts

Purpose: contract lifecycle and e-sign orchestration.

Key columns:
- `id` primary key
- `tenant_id` foreign key to `tenants`
- `contract_number` unique
- `title`
- `description`
- `type` enum: `service`, `nda`, `sla`, `custom`
- `status` enum: `draft`, `sent`, `pending_signature`, `signed`, `expired`, `cancelled`
- `start_date`
- `end_date`
- `signed_at`
- `sent_at`
- `original_pdf_path`
- `signed_pdf_path`
- `signer_name`
- `signer_email`
- `signer_ip`
- `html_content` in-app contract markup/content
- `created_by`
- `assigned_by`
- `created_at`, `updated_at`, `deleted_at`

File-delivery semantics:
- `original_pdf_path` stores the uploaded/generated source document when present.
- `signed_pdf_path` stores the final customer-signed artifact.
- Current API behavior falls back from requested `original` to `signed` when only the signed copy exists.

### contract_sign_fields

Purpose: per-contract signable fields rendered in the e-sign flow.

Key columns:
- `id` primary key
- `contract_id` foreign key to `contracts`
- `field_type` enum: `signature`, `initials`, `date`, `text`, `checkbox`
- `label`
- `page_number`
- `x_position`
- `y_position`
- `width`
- `height`
- `required`
- `value` filled value after signing
- `created_at`, `updated_at`

### contract_files

Purpose: tracked binary files related to contracts.

Key columns:
- `id` primary key
- `contract_id` foreign key to `contracts`
- `tenant_id` scoped tenant column
- `file_type` enum: `original`, `signed`, `amendment`, `attachment`
- `file_path`
- `file_name`
- `mime_type`
- `file_size`
- `uploaded_by`
- `created_at`, `updated_at`

### support_tickets

Purpose: customer support case headers.

Key columns:
- `id` primary key
- `tenant_id` foreign key to `tenants`
- `created_by` customer user id
- `assigned_to` nullable admin assignee id
- `ticket_number` unique
- `subject`
- `description`
- `priority` enum: `low`, `medium`, `high`, `urgent`
- `status` enum: `open`, `in_progress`, `waiting_response`, `resolved`, `closed`
- `category` enum: `billing`, `technical`, `subscription`, `contract`, `general`
- `resolved_at`
- `first_response_at`
- `created_at`, `updated_at`, `deleted_at`

### support_ticket_messages

Purpose: message thread rows for tickets.

Key columns:
- `id` primary key
- `ticket_id` foreign key to `support_tickets`
- `sender_id`
- `sender_type` enum: `customer`, `admin`
- `message`
- `attachments` JSON array
- `is_internal` admin-only note flag
- `read_at`
- `created_at`, `updated_at`

### announcements

Purpose: platform-wide or targeted announcements.

Key columns:
- `id` primary key
- `title`
- `content`
- `type` enum: `info`, `warning`, `success`, `maintenance`, `feature`
- `visibility` enum: `all`, `specific_tenants`, `plan_based`
- `target_tenant_ids` JSON
- `target_plan_slugs` JSON
- `is_published`
- `published_at`
- `expires_at`
- `created_by`
- `created_at`, `updated_at`, `deleted_at`

### audit_logs

Purpose: change and activity audit trail.

Key columns:
- `id` primary key
- `user_id`
- `tenant_id`
- `action`
- `module`
- `entity_id`
- `entity_type`
- `old_values` JSON
- `new_values` JSON
- `ip_address`
- `user_agent`
- `description`
- `created_at`, `updated_at`

Indexes:
- composite index on `module, action`
- composite index on `entity_type, entity_id`

### auth_session_map

Purpose: provider-neutral auth token/session registry used by both web bridge and direct API auth.

Key columns:
- `id` primary key
- `user_id` foreign key to `users`
- `session_token` unique token value
- `provider` enum-like string, currently `laravel` or `dotnet`
- `payload` JSON normalized auth payload
- `ip_address`
- `user_agent`
- `expires_at`
- `created_at`, `updated_at`

## Framework Tables Also Present

These are part of the active schema and are included in the live explorer even though they are framework/infrastructure tables rather than domain entities:
- `cache`
- `cache_locks`
- `jobs`
- `job_batches`
- `failed_jobs`
- `migrations`

## API and Schema Notes

- The live database explorer documents the active connection only; it does not merge multiple environment databases into one composite schema.
- If you need docs for another environment database, point that environment at the desired DB and open `/docs/database` there.
- Customer and admin file delivery now work through API-backed controllers with correct fallback behavior for contracts and direct PDF delivery for invoices.

## .NET Mapping Notes

- Preserve current table and column names for first-pass migration.
- Map enum columns to constrained strings or lookup constraints in SQL Server.
- Preserve money columns as fixed-precision decimals.
- Preserve JSON fields as JSON-capable text columns or typed projections.
- Preserve the auth payload contract in `auth_session_map.payload`.
