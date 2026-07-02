# API Contract Map (Route -> Controller -> Payload Schema)

Source of truth used:
- [routes/api.php](routes/api.php)
- controllers under [app/Http/Controllers/Api/V1](app/Http/Controllers/Api/V1)

## Global Contract

- Base path: /api/v1
- Success envelope:
  - success: true
  - message: string
  - data: object or array
- Validation envelope:
  - success: false
  - message: Validation failed
  - errors: field-to-messages map
- Pagination envelope fields:
  - data, meta, links

## Auth

### POST /auth/login
- Handler: Api\\V1\\AuthController@login
- Request schema:
  - login: string, optional if email present
  - email: string, optional if login present
  - password: string, required
  - corp_id: string(<=30), optional
  - remember: boolean, optional
- Behavior:
  - identity = login or email
  - customer login can be scoped by corp_id
- Response:
  - session payload with user_id, tenant_id, role, display_name, email, session_token, expires_at, permissions

### POST /auth/logout
- Handler: Api\\V1\\AuthController@logout
- Request schema: none
- Response: success message

### GET /auth/me
- Handler: Api\\V1\\AuthController@me
- Request schema: none
- Response: current session payload

## Admin Dashboard

### GET /admin/dashboard
- Handler: Api\\V1\\Admin\\DashboardController@index
- Request schema: none
- Response data:
  - stats
  - recent_tenants (with activeSubscription.plan)
  - recent_tickets

## Admin Tenants

### GET /admin/tenants
- Handler: Api\\V1\\Admin\\TenantController@index
- Query schema:
  - search, status, sort, direction, per_page
- Response: paginated tenant list

### POST /admin/tenants
- Handler: Api\\V1\\Admin\\TenantController@store
- Request schema:
  - company_name: string, required
  - corp_id: string alpha_dash <=30, required
  - contact_name: string, required
  - contact_email: email, required
  - user_email: email, required
  - username: string alpha_dash, required
  - contact_phone: string, optional
  - address: string, optional
  - city: string, optional
  - country: string, optional
  - timezone: string, optional
  - status: active|inactive|trial, optional
  - contact_password: string >=6, optional
  - password: string >=6, optional fallback
  - plan_id: exists plans.id, optional
  - billing_cycle: monthly|quarterly|annual, optional
  - custom_rate: numeric >=0, optional
  - currency: string size 3, optional
- Response: created tenant (with primary user); optional initial subscription assigned

### GET /admin/tenants/{tenant}
- Handler: Api\\V1\\Admin\\TenantController@show
- Response data includes:
  - users
  - subscriptions.plan
  - activeSubscription.plan
  - contracts
  - invoices

### PUT /admin/tenants/{tenant}
- Handler: Api\\V1\\Admin\\TenantController@update
- Request schema:
  - company_name, contact_name, contact_email, contact_phone, status
- Response: updated tenant

### POST /admin/tenants/{tenant}/toggle-status
- Handler: Api\\V1\\Admin\\TenantController@toggleStatus
- Request schema: none
- Response: updated tenant status

### POST /admin/tenants/{tenant}/assign-subscription
- Handler: Api\\V1\\Admin\\TenantController@assignSubscription
- Request schema:
  - plan_id: integer exists plans.id, required
  - billing_cycle: monthly|quarterly|annual, required
  - start_date: date, optional
  - custom_rate: numeric >=0, optional
  - currency: string size 3, optional
  - notes: string, optional
- Response: created subscription
- Pricing contract:
  - base_amount: default plan rate by cycle
  - amount: actual charged rate
  - is_custom_rate: amount != base_amount

### POST /admin/tenants/{tenant}/users/{user}/reset-password
- Handler: Api\\V1\\Admin\\TenantController@resetUserPassword
- Request schema:
  - new_password: string >=8, required
  - new_password_confirmation: string, required
- Response: updated user

## Admin Plans

### GET /admin/plans
- Handler: Api\\V1\\Admin\\PlanController@index
- Query schema:
  - search, is_active, per_page
- Response: paginated plans

### POST /admin/plans
- Handler: Api\\V1\\Admin\\PlanController@store
- Request schema:
  - name: string required
  - description: string optional
  - monthly_price: numeric >=0 required
  - quarterly_price: numeric >=0 required
  - annual_price: numeric >=0 required
  - max_users: int >=1 optional
  - sort_order: int >=0 optional
  - is_active: boolean optional
  - features: string[] optional
- Response: created plan

### GET /admin/plans/{plan}
- Handler: Api\\V1\\Admin\\PlanController@show
- Response: plan

### PUT /admin/plans/{plan}
- Handler: Api\\V1\\Admin\\PlanController@update
- Request schema: same as create but partial
- Response: updated plan

### POST /admin/plans/{plan}/toggle-status
- Handler: Api\\V1\\Admin\\PlanController@toggleStatus
- Request schema: none
- Response: plan with toggled is_active

## Admin Contracts

### GET /admin/contracts
- Handler: Api\\V1\\Admin\\ContractController@index
- Query schema:
  - search, tenant_id, status, date_from, date_to, per_page
- Response: paginated contracts

### POST /admin/contracts
- Handler: Api\\V1\\Admin\\ContractController@store
- Request schema:
  - tenant_id required
  - title required
  - type: service|nda|sla|custom optional
  - start_date, end_date optional
  - signer_email optional
  - html_content optional
  - pdf_file optional (pdf <=10MB)
  - sign_fields optional array
- Response: created contract

### GET /admin/contracts/{contract}
- Handler: Api\\V1\\Admin\\ContractController@show
- Response: contract with tenant, signFields, files

### POST /admin/contracts/{contract}/send
- Handler: Api\\V1\\Admin\\ContractController@sendToCustomer
- Request schema: none
- Response: updated contract state

### GET /admin/contracts/{contract}/download/{type?}
- Handler: Api\\V1\\Admin\\ContractController@download
- Path schema:
  - type optional: original|signed
- Response: file download

## Admin Invoices

### GET /admin/invoices
- Handler: Api\\V1\\Admin\\InvoiceController@index
- Query schema:
  - search, tenant_id, status, per_page
- Response: paginated invoices

### POST /admin/invoices
- Handler: Api\\V1\\Admin\\InvoiceController@store
- Request schema:
  - tenant_id required
  - subscription_id optional
  - issue_date optional
  - due_date optional
  - subtotal required
  - tax_amount optional
  - discount_amount optional
  - total_amount required
  - line_items optional array
  - notes optional
- Response: created invoice

### GET /admin/invoices/{invoice}
- Handler: Api\\V1\\Admin\\InvoiceController@show
- Response: invoice with tenant, subscription.plan, payments

### POST /admin/invoices/{invoice}/payment
- Handler: Api\\V1\\Admin\\InvoiceController@recordPayment
- Request schema:
  - amount required
  - payment_mode required enum: online|bank_transfer|cheque|cash|manual
  - payment_date optional
  - transaction_id optional
  - notes optional
- Response: created payment

### GET /admin/invoices/{invoice}/download-pdf
- Handler: Api\\V1\\Admin\\InvoiceController@downloadPdf
- Response: file download

## Admin Tickets

### GET /admin/tickets
- Handler: Api\\V1\\Admin\\SupportTicketController@index
- Query schema:
  - search, status, priority, tenant_id, per_page
- Response: paginated tickets

### GET /admin/tickets/{supportTicket}
- Handler: Api\\V1\\Admin\\SupportTicketController@show
- Response: ticket with tenant, creator, assignee, messages.sender

### POST /admin/tickets/{supportTicket}/reply
- Handler: Api\\V1\\Admin\\SupportTicketController@reply
- Request schema:
  - message required
  - is_internal boolean optional
- Response: created message

### PATCH /admin/tickets/{supportTicket}/status
- Handler: Api\\V1\\Admin\\SupportTicketController@updateStatus
- Request schema:
  - status required enum: open|in_progress|waiting_response|resolved|closed
- Response: updated ticket

### PATCH /admin/tickets/{supportTicket}/assign
- Handler: Api\\V1\\Admin\\SupportTicketController@assign
- Request schema:
  - admin_id required
- Response: updated ticket

## Admin Announcements

### GET /admin/announcements
- Handler: Api\\V1\\Admin\\AnnouncementController@index
- Query schema:
  - search, is_published, type, per_page
- Response: paginated announcements

### POST /admin/announcements
- Handler: Api\\V1\\Admin\\AnnouncementController@store
- Request schema:
  - title required
  - content required
  - type optional
  - visibility optional: all|specific_tenants|plan_based
  - is_published optional boolean
  - expires_at optional date
  - target_tenant_ids optional array
  - target_plan_slugs optional array
- Response: created announcement

### PUT /admin/announcements/{announcement}
- Handler: Api\\V1\\Admin\\AnnouncementController@update
- Request schema: partial announcement fields
- Response: updated announcement

### POST /admin/announcements/{announcement}/toggle-publish
- Handler: Api\\V1\\Admin\\AnnouncementController@togglePublish
- Request schema: none
- Response: updated announcement

### DELETE /admin/announcements/{announcement}
- Handler: Api\\V1\\Admin\\AnnouncementController@destroy
- Request schema: none
- Response: success

## Admin Audit

### GET /admin/audit
- Handler: Api\\V1\\Admin\\AuditController@index
- Query schema:
  - search, module, date_from, date_to, per_page
- Response: paginated logs with user and tenant

## Customer Dashboard

### GET /customer/dashboard
- Handler: Api\\V1\\Customer\\DashboardController@index
- Response data:
  - tenant
  - active_subscription
  - announcements
  - stats (open_tickets, pending_contracts, unpaid_invoices, subscription_days_left)

## Customer Subscription and Invoices

### GET /customer/subscription
- Handler: Api\\V1\\Customer\\SubscriptionController@currentSubscription
- Response: active subscription with plan and invoices

### GET /customer/plans
- Handler: Api\\V1\\Customer\\SubscriptionController@plans
- Response: active plans ordered by sort_order

### GET /customer/invoices
- Handler: Api\\V1\\Customer\\SubscriptionController@invoices
- Query schema:
  - status, per_page
- Response: paginated invoices

### GET /customer/invoices/{id}
- Handler: Api\\V1\\Customer\\SubscriptionController@invoiceDetail
- Response: invoice with subscription.plan, payments, tenant

### GET /customer/invoices/{id}/download
- Handler: Api\\V1\\Customer\\SubscriptionController@downloadInvoice
- Response: file download

### GET /customer/renewal-preview
- Handler: Api\\V1\\Customer\\SubscriptionController@renewalPreview
- Query schema:
  - billing_cycle optional
- Response:
  - plan, billing_cycle, start_date, end_date, amount, currency

## Customer Contracts

### GET /customer/contracts
- Handler: Api\\V1\\Customer\\ContractController@index
- Query schema:
  - status, per_page
- Response: paginated contracts

### GET /customer/contracts/{id}
- Handler: Api\\V1\\Customer\\ContractController@show
- Response: contract with signFields and files

### POST /customer/contracts/{id}/sign
- Handler: Api\\V1\\Customer\\ContractController@sign
- Request schema:
  - signer_name required
  - signature_data required (base64)
  - fields optional array
- Response: signed contract

### POST /customer/contracts/{id}/upload-signed
- Handler: Api\\V1\\Customer\\ContractController@uploadSigned
- Request schema:
  - signed_pdf required (pdf <=10MB)
- Response: updated contract

### GET /customer/contracts/{id}/download/{type?}
- Handler: Api\\V1\\Customer\\ContractController@download
- Path schema:
  - type optional: original|signed
- Response: file download

## Customer Tickets

### GET /customer/tickets
- Handler: Api\\V1\\Customer\\SupportTicketController@index
- Query schema:
  - status, per_page
- Response: paginated tickets

### POST /customer/tickets
- Handler: Api\\V1\\Customer\\SupportTicketController@store
- Request schema:
  - subject required
  - description required
  - priority optional: low|medium|high|urgent
  - category optional: billing|technical|subscription|contract|general
- Response: created ticket

### GET /customer/tickets/{id}
- Handler: Api\\V1\\Customer\\SupportTicketController@show
- Response: ticket with messages.sender and assignee

### POST /customer/tickets/{id}/reply
- Handler: Api\\V1\\Customer\\SupportTicketController@reply
- Request schema:
  - message required
- Response: created message

## Health

### GET /health
- Handler: closure in [routes/api.php](routes/api.php)
- Response:
  - status: ok
  - version: v1
