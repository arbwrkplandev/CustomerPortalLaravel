@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Customers')
@section('page-subtitle', 'Manage all tenant accounts')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex gap-2 flex-1 max-w-lg">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..." class="form-input flex-1">
        <select name="status" class="form-input w-36">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="trial" @selected(request('status') === 'trial')>Trial</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Customer
    </a>
</div>

<!-- Table -->
<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Subscription</th>
                    <th>Billing Cycle</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tenants as $tenant)
                <tr class="animate-fadeInUp">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                                {{ strtoupper(substr($tenant->company_name, 0, 1)) }}
                            </div>
                            <div>
                                <div class="font-semibold" style="color: var(--color-text)">{{ $tenant->company_name }}</div>
                                <div class="text-xs" style="color: var(--color-text-muted)">{{ $tenant->city }}, {{ $tenant->country }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-medium" style="color: var(--color-text)">{{ $tenant->contact_name }}</div>
                        <div class="text-xs" style="color: var(--color-text-muted)">{{ $tenant->contact_email }}</div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : ($tenant->status === 'trial' ? 'warning' : ($tenant->status === 'suspended' ? 'danger' : 'secondary')) }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                    </td>
                    <td class="text-sm" style="color: var(--color-text-muted)">
                        {{ $tenant->activeSubscription?->plan?->name ?? '—' }}
                    </td>
                    <td class="text-sm capitalize" style="color: var(--color-text-muted)">
                        {{ $tenant->activeSubscription?->billing_cycle ?? '—' }}
                    </td>
                    <td class="text-sm" style="color: var(--color-text-muted)">
                        {{ $tenant->created_at->format('M d, Y') }}
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-primary py-1.5 px-3 text-xs">View</a>
                            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                                @csrf
                                <button type="submit" class="btn {{ $tenant->status === 'active' ? 'btn-warning' : 'btn-success' }} py-1.5 px-3 text-xs">
                                    {{ $tenant->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12" style="color: var(--color-text-muted)">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex items-center justify-between px-2">
        <p class="text-sm" style="color: var(--color-text-muted)">
            Showing {{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }} customers
        </p>
        {{ $tenants->links() }}
    </div>
</div>
@endsection
