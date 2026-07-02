# Laravel Endpoint -> .NET Controller/Action Checklist

Use this file as migration tracker. Keep status updated per endpoint.

Status legend:
- Not Started
- In Progress
- Implemented
- Verified

## Auth Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| POST /api/v1/auth/login | Api.V1.AuthController@login | AuthController.Login | Not Started | Support login or email + optional corp_id |
| POST /api/v1/auth/logout | Api.V1.AuthController@logout | AuthController.Logout | Not Started | Session/token invalidation |
| GET /api/v1/auth/me | Api.V1.AuthController@me | AuthController.Me | Not Started | Return session payload |

## Admin Dashboard Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/dashboard | Api.V1.Admin.DashboardController@index | AdminDashboardController.GetSummary | Not Started | stats + recent tenants + recent tickets |

## Admin Tenant Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/tenants | Api.V1.Admin.TenantController@index | AdminTenantsController.List | Not Started | Pagination contract |
| POST /api/v1/admin/tenants | Api.V1.Admin.TenantController@store | AdminTenantsController.Create | Not Started | corp_id, username/email, optional initial plan |
| GET /api/v1/admin/tenants/{tenant} | Api.V1.Admin.TenantController@show | AdminTenantsController.GetById | Not Started | include activeSubscription.plan |
| PUT /api/v1/admin/tenants/{tenant} | Api.V1.Admin.TenantController@update | AdminTenantsController.Update | Not Started | |
| POST /api/v1/admin/tenants/{tenant}/toggle-status | Api.V1.Admin.TenantController@toggleStatus | AdminTenantsController.ToggleStatus | Not Started | |
| POST /api/v1/admin/tenants/{tenant}/assign-subscription | Api.V1.Admin.TenantController@assignSubscription | AdminTenantsController.AssignSubscription | Not Started | preserve base_amount, amount, is_custom_rate |
| POST /api/v1/admin/tenants/{tenant}/users/{user}/reset-password | Api.V1.Admin.TenantController@resetUserPassword | AdminTenantUsersController.ResetPassword | Not Started | enforce tenant-user ownership |

## Admin Plan Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/plans | Api.V1.Admin.PlanController@index | AdminPlansController.List | Not Started | search, is_active, per_page |
| POST /api/v1/admin/plans | Api.V1.Admin.PlanController@store | AdminPlansController.Create | Not Started | monthly/quarterly/annual rates |
| GET /api/v1/admin/plans/{plan} | Api.V1.Admin.PlanController@show | AdminPlansController.GetById | Not Started | |
| PUT /api/v1/admin/plans/{plan} | Api.V1.Admin.PlanController@update | AdminPlansController.Update | Not Started | |
| POST /api/v1/admin/plans/{plan}/toggle-status | Api.V1.Admin.PlanController@toggleStatus | AdminPlansController.ToggleStatus | Not Started | |

## Admin Contract Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/contracts | Api.V1.Admin.ContractController@index | AdminContractsController.List | Not Started | |
| POST /api/v1/admin/contracts | Api.V1.Admin.ContractController@store | AdminContractsController.Create | Not Started | multipart file support |
| GET /api/v1/admin/contracts/{contract} | Api.V1.Admin.ContractController@show | AdminContractsController.GetById | Not Started | |
| POST /api/v1/admin/contracts/{contract}/send | Api.V1.Admin.ContractController@sendToCustomer | AdminContractsController.Send | Not Started | |
| GET /api/v1/admin/contracts/{contract}/download/{type?} | Api.V1.Admin.ContractController@download | AdminContractsController.Download | Not Started | file stream |

## Admin Invoice Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/invoices | Api.V1.Admin.InvoiceController@index | AdminInvoicesController.List | Not Started | |
| POST /api/v1/admin/invoices | Api.V1.Admin.InvoiceController@store | AdminInvoicesController.Create | Not Started | |
| GET /api/v1/admin/invoices/{invoice} | Api.V1.Admin.InvoiceController@show | AdminInvoicesController.GetById | Not Started | |
| POST /api/v1/admin/invoices/{invoice}/payment | Api.V1.Admin.InvoiceController@recordPayment | AdminInvoicesController.RecordPayment | Not Started | |
| GET /api/v1/admin/invoices/{invoice}/download-pdf | Api.V1.Admin.InvoiceController@downloadPdf | AdminInvoicesController.DownloadPdf | Not Started | file stream |

## Admin Ticket Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/tickets | Api.V1.Admin.SupportTicketController@index | AdminTicketsController.List | Not Started | |
| GET /api/v1/admin/tickets/{supportTicket} | Api.V1.Admin.SupportTicketController@show | AdminTicketsController.GetById | Not Started | |
| POST /api/v1/admin/tickets/{supportTicket}/reply | Api.V1.Admin.SupportTicketController@reply | AdminTicketsController.Reply | Not Started | |
| PATCH /api/v1/admin/tickets/{supportTicket}/status | Api.V1.Admin.SupportTicketController@updateStatus | AdminTicketsController.UpdateStatus | Not Started | |
| PATCH /api/v1/admin/tickets/{supportTicket}/assign | Api.V1.Admin.SupportTicketController@assign | AdminTicketsController.Assign | Not Started | |

## Admin Announcement Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/announcements | Api.V1.Admin.AnnouncementController@index | AdminAnnouncementsController.List | Not Started | |
| POST /api/v1/admin/announcements | Api.V1.Admin.AnnouncementController@store | AdminAnnouncementsController.Create | Not Started | |
| PUT /api/v1/admin/announcements/{announcement} | Api.V1.Admin.AnnouncementController@update | AdminAnnouncementsController.Update | Not Started | |
| POST /api/v1/admin/announcements/{announcement}/toggle-publish | Api.V1.Admin.AnnouncementController@togglePublish | AdminAnnouncementsController.TogglePublish | Not Started | |
| DELETE /api/v1/admin/announcements/{announcement} | Api.V1.Admin.AnnouncementController@destroy | AdminAnnouncementsController.Delete | Not Started | |

## Admin Audit Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/admin/audit | Api.V1.Admin.AuditController@index | AdminAuditController.List | Not Started | search/module/date filters |

## Customer Dashboard Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/customer/dashboard | Api.V1.Customer.DashboardController@index | CustomerDashboardController.GetSummary | Not Started | tenant-scoped |

## Customer Subscription and Billing Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/customer/subscription | Api.V1.Customer.SubscriptionController@currentSubscription | CustomerSubscriptionController.GetCurrent | Not Started | tenant-scoped |
| GET /api/v1/customer/plans | Api.V1.Customer.SubscriptionController@plans | CustomerSubscriptionController.ListPlans | Not Started | |
| GET /api/v1/customer/invoices | Api.V1.Customer.SubscriptionController@invoices | CustomerInvoicesController.List | Not Started | |
| GET /api/v1/customer/invoices/{id} | Api.V1.Customer.SubscriptionController@invoiceDetail | CustomerInvoicesController.GetById | Not Started | |
| GET /api/v1/customer/invoices/{id}/download | Api.V1.Customer.SubscriptionController@downloadInvoice | CustomerInvoicesController.Download | Not Started | file stream |
| GET /api/v1/customer/renewal-preview | Api.V1.Customer.SubscriptionController@renewalPreview | CustomerSubscriptionController.RenewalPreview | Not Started | |

## Customer Contract Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/customer/contracts | Api.V1.Customer.ContractController@index | CustomerContractsController.List | Not Started | |
| GET /api/v1/customer/contracts/{id} | Api.V1.Customer.ContractController@show | CustomerContractsController.GetById | Not Started | |
| POST /api/v1/customer/contracts/{id}/sign | Api.V1.Customer.ContractController@sign | CustomerContractsController.Sign | Not Started | base64 signature |
| POST /api/v1/customer/contracts/{id}/upload-signed | Api.V1.Customer.ContractController@uploadSigned | CustomerContractsController.UploadSigned | Not Started | multipart file |
| GET /api/v1/customer/contracts/{id}/download/{type?} | Api.V1.Customer.ContractController@download | CustomerContractsController.Download | Not Started | file stream |

## Customer Ticket Module

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/customer/tickets | Api.V1.Customer.SupportTicketController@index | CustomerTicketsController.List | Not Started | |
| POST /api/v1/customer/tickets | Api.V1.Customer.SupportTicketController@store | CustomerTicketsController.Create | Not Started | |
| GET /api/v1/customer/tickets/{id} | Api.V1.Customer.SupportTicketController@show | CustomerTicketsController.GetById | Not Started | |
| POST /api/v1/customer/tickets/{id}/reply | Api.V1.Customer.SupportTicketController@reply | CustomerTicketsController.Reply | Not Started | |

## Health

| Endpoint | Laravel Handler | Suggested .NET Target | Status | Notes |
|---|---|---|---|---|
| GET /api/v1/health | routes/api.php closure | HealthController.Get | Not Started | lightweight uptime endpoint |

## Suggested Execution Strategy

1. Implement Auth and Tenant modules first.
2. Implement Plan and Subscription pricing rules next.
3. Implement Invoices and Contracts.
4. Implement Tickets and Announcements.
5. Implement Audit and health endpoint.
6. Run parity tests against [docs/API_CONTRACT_MAP.md](docs/API_CONTRACT_MAP.md).
