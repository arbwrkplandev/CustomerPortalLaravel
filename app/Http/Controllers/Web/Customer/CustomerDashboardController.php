<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\CustomerSubscription;
use App\Models\Contract;
use App\Services\Admin\AnnouncementService;
use Illuminate\Support\Facades\Auth;

class CustomerDashboardController extends Controller
{
    public function __construct(protected AnnouncementService $announcementService) {}

    public function index()
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $activeSubscription = CustomerSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->first();

        $daysLeft = $activeSubscription
            ? now()->diffInDays($activeSubscription->end_date, false)
            : null;

        $planSlug = $activeSubscription?->plan?->slug;
        $announcements = $this->announcementService->getForTenant($tenantId, $planSlug);

        $pendingContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'pending_signature')
            ->get();

        $stats = [
            'open_tickets'      => \App\Models\SupportTicket::where('tenant_id', $tenantId)->whereNotIn('status', ['resolved', 'closed'])->count(),
            'pending_contracts' => $pendingContracts->count(),
            'unpaid_invoices'   => \App\Models\Invoice::where('tenant_id', $tenantId)->whereIn('status', ['sent', 'overdue'])->count(),
            'signed_contracts'  => Contract::where('tenant_id', $tenantId)->where('status', 'signed')->count(),
        ];

        return view('customer.dashboard', compact(
            'activeSubscription', 'daysLeft', 'announcements',
            'pendingContracts', 'stats'
        ));
    }
}
