@extends('layouts.app')
@section('title', $tenant->name)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline">← Customers</a>
        <div class="flex-1">
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">{{ $tenant->name }}</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $tenant->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-outline">Edit</a>
            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn {{ $tenant->is_active ? 'btn-warning' : 'btn-success' }}">
                    {{ $tenant->is_active ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <!-- Info Card -->
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Company Details</h3>
                <div class="space-y-3 text-sm">
                    @if($tenant->phone)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Phone</span>
                        <span style="color: var(--color-text-primary)">{{ $tenant->phone }}</span>
                    </div>
                    @endif
                    @if($tenant->industry)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Industry</span>
                        <span style="color: var(--color-text-primary)">{{ $tenant->industry }}</span>
                    </div>
                    @endif
                    @if($tenant->website)
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Website</span>
                        <a href="{{ $tenant->website }}" target="_blank" class="text-indigo-400 hover:underline">{{ parse_url($tenant->website, PHP_URL_HOST) }}</a>
                    </div>
                    @endif
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Status</span>
                        <span class="badge {{ $tenant->is_active ? 'badge-success' : 'badge-danger' }}">{{ $tenant->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color: var(--color-text-secondary)">Since</span>
                        <span style="color: var(--color-text-primary)">{{ $tenant->created_at->format('M Y') }}</span>
                    </div>
                </div>
            </div>

            <!-- Assign Subscription -->
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Assign Subscription</h3>
                <form method="POST" action="{{ route('admin.tenants.assign-subscription', $tenant) }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="form-label text-xs">Plan</label>
                        <select name="plan_id" class="form-input" required>
                            <option value="">Select plan</option>
                            @foreach($plans as $plan)
                            <option value="{{ $plan->id }}" {{ $tenant->activeSubscription()?->plan_id == $plan->id ? 'selected' : '' }}>
                                {{ $plan->name }} — ${{ number_format($plan->price_monthly, 0) }}/mo
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="form-label text-xs">Billing Cycle</label>
                        <select name="billing_cycle" class="form-input">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annual">Annual</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-full justify-center">Assign Plan</button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <!-- Active Subscription Banner -->
            @if($tenant->activeSubscription())
            <div class="card" style="border: 1px solid rgba(99,102,241,0.3); background: linear-gradient(135deg, rgba(99,102,241,0.08), transparent)">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider mb-1" style="color: var(--color-brand-primary)">Active Plan</div>
                        <div class="text-xl font-black" style="color: var(--color-text-primary)">{{ $tenant->activeSubscription()->plan->name }}</div>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-black" style="color: var(--color-brand-primary)">${{ number_format($tenant->activeSubscription()->price_paid ?? 0, 0) }}</div>
                        <div class="text-xs" style="color: var(--color-text-secondary)">/{{ $tenant->activeSubscription()->billing_cycle }}</div>
                    </div>
                </div>
                @if($tenant->activeSubscription()->ends_at)
                <div class="mt-3 text-sm" style="color: var(--color-text-secondary)">
                    Renews {{ \Carbon\Carbon::parse($tenant->activeSubscription()->ends_at)->format('M d, Y') }}
                </div>
                @endif
            </div>
            @endif

            <!-- Users -->
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Users ({{ $tenant->users->count() }})</h3>
                @if($tenant->users->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-secondary)">No users yet.</p>
                @else
                <table class="data-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach($tenant->users as $user)
                        <tr>
                            <td style="color: var(--color-text-primary)">{{ $user->name }}</td>
                            <td style="color: var(--color-text-secondary)">{{ $user->email }}</td>
                            <td><span class="badge" style="background: var(--color-surface-2)">{{ ucfirst($user->role) }}</span></td>
                            <td><span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>

            <!-- Recent Invoices -->
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold" style="color: var(--color-text-primary)">Recent Invoices</h3>
                    <a href="{{ route('admin.invoices.index') }}?tenant_id={{ $tenant->id }}" class="text-sm" style="color: var(--color-brand-primary)">View all →</a>
                </div>
                @if($tenant->invoices->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-secondary)">No invoices.</p>
                @else
                <table class="data-table">
                    <thead><tr><th>Invoice #</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                        @foreach($tenant->invoices->take(5) as $invoice)
                        <tr>
                            <td class="font-mono text-sm" style="color: var(--color-brand-primary)">{{ $invoice->invoice_number }}</td>
                            <td style="color: var(--color-text-primary)">${{ number_format($invoice->total_amount, 2) }}</td>
                            <td><span class="badge {{ $invoice->status === 'paid' ? 'badge-success' : ($invoice->status === 'overdue' ? 'badge-danger' : 'badge-warning') }}">{{ ucfirst($invoice->status) }}</span></td>
                            <td style="color: var(--color-text-secondary)">{{ $invoice->created_at->format('M d, Y') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
