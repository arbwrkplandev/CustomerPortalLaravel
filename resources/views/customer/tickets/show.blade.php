@extends('layouts.app')
@section('title', 'Ticket: ' . $ticket->subject)
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-3xl">
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('customer.tickets') }}" class="btn btn-outline">← Back</a>
        <div class="flex-1">
            <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $ticket->subject }}</h1>
            <div class="flex items-center gap-3 mt-1">
                @if($ticket->status === 'open') <span class="badge badge-success">Open</span>
                @elseif($ticket->status === 'in_progress') <span class="badge badge-info">In Progress</span>
                @elseif($ticket->status === 'waiting_customer') <span class="badge badge-warning">Awaiting Your Reply</span>
                @elseif($ticket->status === 'resolved') <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Resolved</span>
                @endif
                <span class="text-sm" style="color: var(--color-text-secondary)">Opened {{ $ticket->created_at->diffForHumans() }}</span>
            </div>
        </div>
    </div>

    <!-- Messages -->
    <div class="space-y-4 mb-6">
        @foreach($ticket->messages as $message)
        <div class="card {{ $message->sender_type === 'admin' ? 'border-l-4' : '' }}"
             style="{{ $message->sender_type === 'admin' ? 'border-left-color: var(--color-brand-primary)' : '' }}">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold"
                         style="background: {{ $message->sender_type === 'admin' ? 'var(--color-brand-primary)' : 'var(--color-brand-secondary)' }}">
                        {{ strtoupper(substr($message->sender->name ?? 'U', 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-semibold text-sm" style="color: var(--color-text-primary)">
                            {{ $message->sender->name ?? 'Unknown' }}
                            @if($message->sender_type === 'admin')
                                <span class="text-xs ml-1 font-normal" style="color: var(--color-brand-primary)">· Support Team</span>
                            @endif
                        </div>
                    </div>
                </div>
                <span class="text-xs" style="color: var(--color-text-secondary)">{{ $message->created_at->diffForHumans() }}</span>
            </div>
            <div class="text-sm whitespace-pre-wrap" style="color: var(--color-text-secondary)">{{ $message->message }}</div>
        </div>
        @endforeach
    </div>

    <!-- Reply Form -->
    @if(!in_array($ticket->status, ['resolved', 'closed']))
    <div class="card">
        <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Reply</h3>
        @if(session('success'))
        <div class="mb-4 p-3 rounded-lg" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
            <p class="text-green-400 text-sm">{{ session('success') }}</p>
        </div>
        @endif
        <form method="POST" action="{{ route('customer.tickets.reply', $ticket) }}" class="space-y-4">
            @csrf
            <div>
                <textarea name="message" rows="4" required class="form-input"
                          placeholder="Type your reply...">{{ old('message') }}</textarea>
                @error('message') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-primary">Send Reply</button>
        </form>
    </div>
    @else
        <div class="card text-center py-8" style="border: 1px solid rgba(16,185,129,0.3)">
            <p class="font-semibold" style="color: var(--color-text-primary)">✅ This ticket has been resolved</p>
            <p class="text-sm mt-1" style="color: var(--color-text-secondary)">Need further help? Open a new ticket.</p>
        </div>
    @endif
</div>
@endsection
