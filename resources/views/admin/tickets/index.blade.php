@extends('layouts.app')
@section('title', 'Support Tickets')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Support Tickets</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Customer support inbox</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1 min-w-48" placeholder="Search tickets...">
            <select name="status" class="form-input w-44">
                <option value="">All Status</option>
                @foreach(['open', 'in_progress', 'waiting_customer', 'resolved', 'closed'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                @endforeach
            </select>
            <select name="priority" class="form-input w-32">
                <option value="">All Priority</option>
                @foreach(['low', 'medium', 'high', 'critical'] as $p)
                <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if(request()->anyFilled(['search', 'status', 'priority'])) <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline">Clear</a> @endif
        </form>
    </div>

    @if($tickets->isEmpty())
        <div class="card text-center py-16">
            <div class="text-5xl mb-4">🎉</div>
            <h3 class="text-lg font-semibold" style="color: var(--color-text-primary)">No tickets found</h3>
            <p style="color: var(--color-text-secondary)">All caught up!</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Assigned To</th>
                        <th>Last Update</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ Str::limit($ticket->subject, 50) }}</div>
                            <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">{{ $ticket->category }}</div>
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $ticket->tenant->company_name ?? $ticket->tenant->name }}</td>
                        <td>
                            @if($ticket->priority === 'critical') <span class="badge badge-danger">Critical</span>
                            @elseif($ticket->priority === 'high') <span class="badge badge-warning">High</span>
                            @elseif($ticket->priority === 'medium') <span class="badge badge-info">Medium</span>
                            @else <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Low</span>
                            @endif
                        </td>
                        <td>
                            @if($ticket->status === 'open') <span class="badge badge-success">Open</span>
                            @elseif($ticket->status === 'in_progress') <span class="badge badge-info">In Progress</span>
                            @elseif($ticket->status === 'waiting_customer') <span class="badge badge-warning">Waiting</span>
                            @elseif($ticket->status === 'resolved') <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Resolved</span>
                            @else <span class="badge">{{ ucfirst($ticket->status) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $ticket->assignee?->name ?? '—' }}</td>
                        <td style="color: var(--color-text-secondary)">{{ $ticket->updated_at->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="btn btn-outline btn-sm">View</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $tickets->links() }}</div>
    @endif
</div>
@endsection
