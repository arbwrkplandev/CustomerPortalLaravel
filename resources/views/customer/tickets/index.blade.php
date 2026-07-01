@extends('layouts.app')
@section('title', 'Support Tickets')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Support Tickets</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Get help from our team</p>
        </div>
        <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Ticket
        </a>
    </div>

    @if($tickets->isEmpty())
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">No tickets yet</h3>
            <p class="mb-6" style="color: var(--color-text-secondary)">Need help? Open a support ticket and our team will respond shortly.</p>
            <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary">Open a Ticket</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Last Update</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tickets as $ticket)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $ticket->subject }}</div>
                            @if($ticket->messages_count ?? false)
                                <div class="text-xs mt-0.5" style="color: var(--color-text-secondary)">{{ $ticket->messages_count }} messages</div>
                            @endif
                        </td>
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
                            @elseif($ticket->status === 'waiting_customer') <span class="badge badge-warning">Awaiting You</span>
                            @elseif($ticket->status === 'resolved') <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Resolved</span>
                            @else <span class="badge">{{ ucfirst($ticket->status) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $ticket->updated_at->diffForHumans() }}</td>
                        <td class="text-right">
                            <a href="{{ route('customer.tickets.show', $ticket) }}" class="btn btn-outline btn-sm">View Thread</a>
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
