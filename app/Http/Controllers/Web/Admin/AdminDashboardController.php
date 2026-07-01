<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\SupportTicket;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_tenants'    => Tenant::count(),
            'active_tenants'   => Tenant::where('status', 'active')->count(),
            'open_tickets'     => SupportTicket::whereNotIn('status', ['resolved', 'closed'])->count(),
            'urgent_tickets'   => SupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolved', 'closed'])->count(),
            'signed_contracts' => Contract::where('status', 'signed')->count(),
            'pending_signature'=> Contract::where('status', 'pending_signature')->count(),
            'paid_invoices'    => Invoice::where('status', 'paid')->count(),
            'mtd_revenue'      => Payment::where('status', 'completed')->whereMonth('payment_date', now()->month)->sum('amount'),
        ];

        $recentTenants = Tenant::with(['subscriptions' => fn($q) => $q->where('status','active')->with('plan')])
            ->latest()->limit(8)->get();

        $recentTickets = SupportTicket::with(['tenant'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()->limit(6)->get();

        return view('admin.dashboard', compact('stats', 'recentTenants', 'recentTickets'));
    }
}
