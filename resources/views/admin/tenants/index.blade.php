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
                    <td>
                        @php $sub = $tenant->active_subscription ?? $tenant->activeSubscription ?? null; @endphp
                        @if($sub && ($sub->plan->name ?? $sub->plan_name ?? null))
                        <div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(99,102,241,0.12); color: #6366f1; border: 1px solid rgba(99,102,241,0.25)">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                {{ $sub->plan->name ?? $sub->plan_name ?? '—' }}
                            </span>
                            @if($sub->is_custom_rate ?? false)
                            <span class="ml-1 text-xs font-semibold text-amber-500">★</span>
                            @endif
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--color-text-muted)">
                            {{ ($sub->currency ?? 'USD') }} {{ number_format($sub->amount ?? 0, 0) }}/{{ $sub->billing_cycle ?? '' }}
                        </div>
                        @else
                        <span class="text-xs px-2 py-0.5 rounded-full" style="background:var(--color-surface-2); color:var(--color-text-muted)">No plan</span>
                        @endif
                    </td>
                    <td class="text-sm capitalize" style="color: var(--color-text-muted)">
                        @php $sub2 = $tenant->active_subscription ?? $tenant->activeSubscription ?? null; @endphp
                        @if($sub2 && $sub2->billing_cycle)
                        <span class="inline-flex items-center gap-1">
                            @if(($sub2->billing_cycle ?? '') === 'monthly')
                                <span style="color:#6366f1">●</span>
                            @elseif(($sub2->billing_cycle ?? '') === 'quarterly')
                                <span style="color:#f59e0b">●</span>
                            @else
                                <span style="color:#10b981">●</span>
                            @endif
                            {{ ucfirst($sub2->billing_cycle) }}
                        </span>
                        @else
                        —
                        @endif
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
