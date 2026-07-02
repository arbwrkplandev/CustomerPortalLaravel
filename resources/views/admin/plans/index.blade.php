@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Plans')
@section('page-subtitle', 'Manage subscription plans and pricing rates')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex gap-2 flex-1 max-w-lg">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plans..." class="form-input flex-1">
        <select name="status" class="form-input w-36">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('admin.plans.create') }}" class="btn btn-primary">Add Plan</a>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Plan</th>
                    <th>Monthly</th>
                    <th>Quarterly</th>
                    <th>Annual</th>
                    <th>Max Users</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                <tr>
                    <td>
                        <div class="font-semibold" style="color: var(--color-text)">{{ $plan->name }}</div>
                        <div class="text-xs" style="color: var(--color-text-muted)">{{ Str::limit($plan->description, 80) }}</div>
                    </td>
                    <td>${{ number_format($plan->monthly_price, 2) }}</td>
                    <td>${{ number_format($plan->quarterly_price, 2) }}</td>
                    <td>${{ number_format($plan->annual_price, 2) }}</td>
                    <td>{{ number_format($plan->max_users) }}</td>
                    <td>
                        <span class="badge {{ $plan->is_active ? 'badge-success' : 'badge-danger' }}">{{ $plan->is_active ? 'Active' : 'Inactive' }}</span>
                    </td>
                    <td>
                        <div class="flex gap-2">
                            <a href="{{ route('admin.plans.edit', $plan) }}" class="btn btn-outline py-1.5 px-3 text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.plans.toggle-status', $plan) }}">
                                @csrf
                                <button type="submit" class="btn {{ $plan->is_active ? 'btn-warning' : 'btn-success' }} py-1.5 px-3 text-xs">
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-10" style="color: var(--color-text-muted)">No plans found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $plans->links() }}</div>
</div>
@endsection
