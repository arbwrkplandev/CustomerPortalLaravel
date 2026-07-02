<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SupportTicket;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $stats = [
            'total_tenants' => Tenant::count(),
            'active_tenants' => Tenant::where('status', 'active')->count(),
            'open_tickets' => SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgent_tickets' => SupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'signed_contracts' => Contract::where('status', 'signed')->count(),
            'pending_signature' => Contract::where('status', 'pending_signature')->count(),
            'paid_invoices' => Invoice::where('status', 'paid')->count(),
            'mtd_revenue' => Payment::where('status', 'completed')->whereMonth('payment_date', now()->month)->sum('amount'),
        ];

        $recentTenants = Tenant::with(['activeSubscription.plan'])->latest()->limit(8)->get();

        $recentTickets = SupportTicket::with(['tenant'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->limit(6)
            ->get();

        return $this->success([
            'stats' => $stats,
            'recent_tenants' => $recentTenants,
            'recent_tickets' => $recentTickets,
        ]);
    }
}
