<?php

namespace App\Http\Controllers\Api;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   version="1.0.0",
 *   title="WrkPlan REST API",
 *   description="Multi-tenant customer portal and admin hub. Auth layer is swappable between Laravel and .NET providers via AUTH_PROVIDER env variable. All endpoints versioned at /api/v1/",
 *   @OA\Contact(email="api@wrkplan.com")
 * )
 * @OA\Server(url="/", description="WrkPlan Platform")
 * @OA\SecurityScheme(
 *   securityScheme="session",
 *   type="apiKey",
 *   in="cookie",
 *   name="wrkplan_session"
 * )
 * @OA\Tag(name="Authentication", description="Login, logout, session payload")
 * @OA\Tag(name="Admin - Tenants", description="Customer and tenant management")
 * @OA\Tag(name="Admin - Contracts", description="Contract lifecycle and e-sign")
 * @OA\Tag(name="Admin - Invoices", description="Invoice and payment management")
 * @OA\Tag(name="Admin - Support Tickets", description="Support ticket inbox")
 * @OA\Tag(name="Admin - Announcements", description="Announcement management")
 * @OA\Tag(name="Customer - Dashboard", description="Customer portal overview")
 * @OA\Tag(name="Customer - Contracts", description="Contract viewing and signing")
 * @OA\Tag(name="Customer - Support Tickets", description="Customer support requests")
 * @OA\Tag(name="Customer - Subscriptions & Invoices", description="Billing and invoices")
 */
class SwaggerAnnotations {}
