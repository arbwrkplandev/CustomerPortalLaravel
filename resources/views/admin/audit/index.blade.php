@extends('layouts.app')
@section('title', 'Audit Logs')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="mb-8">
        <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Audit Logs</h1>
        <p class="mt-1" style="color: var(--color-text-secondary)">Complete trail of all platform activity</p>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1 min-w-48" placeholder="Search action, entity...">
            <select name="action" class="form-input w-36">
                <option value="">All Actions</option>
                @foreach(['create', 'update', 'delete', 'login', 'logout', 'sign', 'send'] as $a)
                <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucfirst($a) }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-input w-40">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-input w-40">
            <button type="submit" class="btn btn-outline">Filter</button>
            @if(request()->anyFilled(['search', 'action', 'date_from', 'date_to'])) <a href="{{ route('admin.audit.index') }}" class="btn btn-outline">Clear</a> @endif
        </form>
    </div>

    <div class="card overflow-hidden">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Details</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                <tr>
                    <td class="text-xs" style="color: var(--color-text-secondary); white-space: nowrap">
                        {{ $log->created_at->format('M d H:i:s') }}
                    </td>
                    <td style="color: var(--color-text-secondary)">
                        {{ $log->user?->name ?? 'System' }}
                    </td>
                    <td>
                        @php
                            $actionColors = ['create' => 'badge-success', 'delete' => 'badge-danger', 'login' => 'badge-info', 'update' => 'badge-warning'];
                        @endphp
                        <span class="badge {{ $actionColors[$log->action] ?? '' }}">{{ ucfirst($log->action) }}</span>
                    </td>
                    <td style="color: var(--color-text-secondary)">
                        {{ $log->auditable_type ? class_basename($log->auditable_type) : '—' }}
                        @if($log->auditable_id) <span class="text-xs">#{{ $log->auditable_id }}</span> @endif
                    </td>
                    <td class="text-xs max-w-xs truncate" style="color: var(--color-text-secondary)" title="{{ $log->description }}">
                        {{ Str::limit($log->description, 60) }}
                    </td>
                    <td class="text-xs font-mono" style="color: var(--color-text-secondary)">{{ $log->ip_address ?? '—' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8" style="color: var(--color-text-secondary)">No audit logs found.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
</div>
@endsection
