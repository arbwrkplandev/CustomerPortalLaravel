@extends('layouts.app')
@section('title', 'Ticket: ' . $ticket->subject)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-3xl">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('admin.tickets.index') }}" class="btn btn-outline">← Back</a>
        <div class="flex-1">
            <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $ticket->subject }}</h1>
            <div class="flex items-center gap-3 mt-1 flex-wrap">
                @if($ticket->priority === 'critical') <span class="badge badge-danger">Critical</span>
                @elseif($ticket->priority === 'high') <span class="badge badge-warning">High</span>
                @elseif($ticket->priority === 'medium') <span class="badge badge-info">Medium</span>
                @else <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Low</span>
                @endif
                @if($ticket->status === 'open') <span class="badge badge-success">Open</span>
                @elseif($ticket->status === 'in_progress') <span class="badge badge-info">In Progress</span>
                @elseif(in_array($ticket->status, ['waiting_customer', 'waiting_response'])) <span class="badge badge-warning">Awaiting Customer</span>
                @elseif($ticket->status === 'resolved') <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Resolved</span>
                @endif
                <span class="text-sm" style="color: var(--color-text-secondary)">{{ $ticket->tenant->company_name ?? $ticket->tenant->name }} · {{ $ticket->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 p-3 rounded-xl" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400 text-sm">{{ session('success') }}</p>
    </div>
    @endif

    <!-- Status & Assign Controls -->
    <div class="card mb-6">
        <div class="flex flex-wrap gap-4">
            <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}" class="flex items-center gap-2">
                @csrf @method('PATCH')
                <select name="status" class="form-input">
                    @foreach(['open', 'in_progress', 'waiting_response', 'resolved', 'closed'] as $s)
                    <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline btn-sm">Update Status</button>
            </form>
            <form method="POST" action="{{ route('admin.tickets.assign', $ticket) }}" class="flex items-center gap-2">
                @csrf @method('PATCH')
                <select name="admin_id" class="form-input">
                    @foreach($admins as $admin)
                    <option value="{{ $admin->id }}" {{ $ticket->assigned_to == $admin->id ? 'selected' : '' }}>{{ $admin->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline btn-sm">Assign</button>
            </form>
        </div>
    </div>

    <!-- Messages Thread -->
    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $message)
        @if(!$message->is_internal)
        <div class="card {{ $message->sender_type === 'admin' ? '' : '' }}"
             style="{{ $message->sender_type === 'customer' ? 'border-left: 4px solid var(--color-brand-secondary)' : 'border-left: 4px solid var(--color-brand-primary)' }}">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                         style="background: {{ $message->sender_type === 'admin' ? 'var(--color-brand-primary)' : 'var(--color-brand-secondary)' }}">
                        {{ strtoupper(substr($message->sender->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-sm" style="color: var(--color-text-primary)">
                            {{ $message->sender->name ?? 'Unknown' }}
                            @if($message->sender_type === 'admin') <span class="text-xs ml-1 font-normal" style="color: var(--color-brand-primary)">· Support</span> @endif
                        </div>
                    </div>
                </div>
                <span class="text-xs" style="color: var(--color-text-secondary)">{{ $message->created_at->diffForHumans() }}</span>
            </div>
            <div class="text-sm whitespace-pre-wrap" style="color: var(--color-text-secondary)">{{ $message->message }}</div>
        </div>
        @endif
        @endforeach
    </div>

    <!-- Reply & Internal Note -->
    @if(!in_array($ticket->status, ['closed']))
    <div class="card" x-data="{ tab: 'reply' }">
        <div class="flex gap-4 mb-4 border-b pb-4" style="border-color: var(--color-border)">
            <button @click="tab = 'reply'" :class="tab === 'reply' ? 'font-bold' : ''" style="color: var(--color-text-primary)">Customer Reply</button>
            <button @click="tab = 'internal'" :class="tab === 'internal' ? 'font-bold' : ''" style="color: var(--color-text-secondary)">Internal Note</button>
        </div>
        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}" class="space-y-4">
            @csrf
            <input type="hidden" name="is_internal" :value="tab === 'internal' ? 1 : 0">
            <textarea name="message" rows="4" required class="form-input"
                      :placeholder="tab === 'internal' ? 'Internal note (not visible to customer)...' : 'Reply to customer...'">{{ old('message') }}</textarea>
            <button type="submit" class="btn btn-primary">
                <span x-text="tab === 'internal' ? 'Save Internal Note' : 'Send Reply'"></span>
            </button>
        </form>
    </div>
    @endif
</div>
@endsection
