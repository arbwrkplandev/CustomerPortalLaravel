@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Admin Dashboard')
@section('page-subtitle', 'Platform overview and key metrics')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<!-- Stat Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8 stagger-children">
    <div class="stat-card animate-fadeInUp" style="background: linear-gradient(135deg, #6366f1, #4f46e5)">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/70 text-sm font-semibold uppercase tracking-wide">Total Customers</p>
                <p class="text-4xl font-black text-white mt-1">{{ $stats['total_tenants'] }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $stats['active_tenants'] }} active</p>
            </div>
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: rgba(255,255,255,0.15)">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed)">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/70 text-sm font-semibold uppercase tracking-wide">Revenue (MTD)</p>
                <p class="text-4xl font-black text-white mt-1">${{ number_format($stats['mtd_revenue'], 0) }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $stats['paid_invoices'] }} invoices paid</p>
            </div>
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: rgba(255,255,255,0.15)">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="background: linear-gradient(135deg, #06b6d4, #0891b2)">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/70 text-sm font-semibold uppercase tracking-wide">Open Tickets</p>
                <p class="text-4xl font-black text-white mt-1">{{ $stats['open_tickets'] }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $stats['urgent_tickets'] }} urgent</p>
            </div>
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: rgba(255,255,255,0.15)">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="background: linear-gradient(135deg, #10b981, #059669)">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-white/70 text-sm font-semibold uppercase tracking-wide">Contracts Signed</p>
                <p class="text-4xl font-black text-white mt-1">{{ $stats['signed_contracts'] }}</p>
                <p class="text-white/60 text-xs mt-1">{{ $stats['pending_signature'] }} pending</p>
            </div>
            <div class="w-14 h-14 rounded-2xl flex items-center justify-center" style="background: rgba(255,255,255,0.15)">
                <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    <!-- Recent Customers -->
    <div class="xl:col-span-2 card animate-fadeInUp">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg" style="color: var(--color-text)">Recent Customers</h3>
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary text-sm py-2">Add Customer</a>
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline text-sm py-2">View All</a>
            </div>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Plan</th>
                        <th>Joined</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTenants as $tenant)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text)">{{ $tenant->company_name }}</div>
                            <div class="text-xs" style="color: var(--color-text-muted)">{{ $tenant->contact_email }}</div>
                        </td>
                        <td>
                            <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : ($tenant->status === 'trial' ? 'warning' : 'danger') }}">
                                {{ ucfirst($tenant->status) }}
                            </span>
                        </td>
                        <td class="text-sm" style="color: var(--color-text-muted)">
                            {{ $tenant->activeSubscription?->plan?->name ?? 'No Plan' }}
                        </td>
                        <td class="text-sm" style="color: var(--color-text-muted)">
                            {{ $tenant->created_at->format('M d, Y') }}
                        </td>
                        <td>
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-primary py-1.5 px-3 text-xs">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Tickets -->
    <div class="card animate-fadeInUp">
        <div class="flex items-center justify-between mb-6">
            <h3 class="font-bold text-lg" style="color: var(--color-text)">Recent Tickets</h3>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline text-sm py-2">All</a>
        </div>
        <div class="space-y-3">
            @foreach($recentTickets as $ticket)
            <a href="{{ route('admin.tickets.show', $ticket) }}" class="block p-4 rounded-xl transition-all hover:scale-[1.01]"
               style="background: var(--color-surface-alt); border: 1px solid var(--color-border)">
                <div class="flex items-start justify-between gap-2 mb-1">
                    <span class="font-semibold text-sm truncate" style="color: var(--color-text)">{{ $ticket->subject }}</span>
                    <span class="badge badge-{{ $ticket->priority === 'urgent' ? 'danger' : ($ticket->priority === 'high' ? 'warning' : 'info') }} text-xs flex-shrink-0">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
                <div class="text-xs" style="color: var(--color-text-muted)">
                    {{ $ticket->tenant->company_name }} &bull; {{ $ticket->created_at->diffForHumans() }}
                </div>
            </a>
            @endforeach
        </div>
    </div>
</div>
@endsection
