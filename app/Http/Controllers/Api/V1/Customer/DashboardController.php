<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\AnnouncementService;
use App\Services\Admin\ContractService;
use App\Services\Admin\InvoiceService;
use App\Services\Admin\SupportTicketService;
use App\Models\CustomerSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(name="Customer - Dashboard", description="Customer portal dashboard data")
 */
class DashboardController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AnnouncementService $announcementService,
        protected ContractService $contractService,
        protected InvoiceService $invoiceService,
        protected SupportTicketService $ticketService,
    ) {}

    /**
     * @OA\Get(path="/api/v1/customer/dashboard", tags={"Customer - Dashboard"}, summary="Dashboard summary",
     *   @OA\Response(response=200, description="Dashboard data")
     * )
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $tenant = $user->tenant()->with(['subscriptions.plan'])->first();

        $activeSubscription = CustomerSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        $planSlug = $activeSubscription?->plan?->slug;

        $announcements = $this->announcementService->getForTenant($tenantId, $planSlug);

        $openTickets = \App\Models\SupportTicket::where('tenant_id', $tenantId)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->count();

        $pendingContracts = \App\Models\Contract::where('tenant_id', $tenantId)
            ->where('status', 'pending_signature')
            ->count();

        $unpaidInvoices = \App\Models\Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue'])
            ->count();

        return $this->success([
            'tenant'              => $tenant,
            'active_subscription' => $activeSubscription,
            'announcements'       => $announcements->take(5),
            'stats'               => [
                'open_tickets'      => $openTickets,
                'pending_contracts' => $pendingContracts,
                'unpaid_invoices'   => $unpaidInvoices,
                'subscription_days_left' => $activeSubscription
                    ? now()->diffInDays($activeSubscription->end_date, false)
                    : null,
            ],
        ]);
    }
}
