@extends('layouts.app')
@section('portal-name', 'Customer Portal')
@section('page-title', 'My Dashboard')
@section('page-subtitle', 'Welcome back, {{ auth()->user()->name }}')

@section('sidebar-nav')
@php $route = request()->route()->getName(); @endphp
<a href="{{ route('customer.dashboard') }}" class="sidebar-link {{ $route === 'customer.dashboard' ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
    Dashboard
</a>
<a href="{{ route('customer.subscription') }}" class="sidebar-link {{ Str::startsWith($route, 'customer.subscription') ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
    Subscription
</a>
<a href="{{ route('customer.invoices') }}" class="sidebar-link {{ Str::startsWith($route, 'customer.invoices') ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
    Invoices
</a>
<a href="{{ route('customer.contracts') }}" class="sidebar-link {{ Str::startsWith($route, 'customer.contracts') ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    Contracts
</a>
<a href="{{ route('customer.tickets') }}" class="sidebar-link {{ Str::startsWith($route, 'customer.tickets') ? 'active' : '' }}">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
    Support
</a>
@endsection

@section('content')
<!-- Welcome Banner -->
<div class="mb-6 p-5 rounded-2xl animate-fadeInUp" style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(59,130,246,0.08)); border: 1px solid rgba(16,185,129,0.25)">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h2 class="text-xl font-black" style="color: var(--color-text)">
                {{ $greeting }}, {{ auth()->user()->name }}
            </h2>
            <p class="text-sm mt-1" style="color: var(--color-text-muted)">
                {{ $motivation }}
            </p>
        </div>
        <div class="text-sm lg:text-right" style="color: var(--color-text-muted)">
            <div class="font-semibold" style="color: var(--color-text)">
                {{ $tenant?->country ?: 'Your Region' }} Time
            </div>
            <div>{{ $localNow->format('D, M d, Y h:i A') }}</div>
            <div class="text-xs">{{ $timezone }}</div>
        </div>
    </div>
</div>

<!-- Subscription Banner -->
@if($activeSubscription)
<div class="mb-6 p-5 rounded-2xl flex items-center gap-4 animate-fadeInUp"
     style="background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.1)); border: 1px solid rgba(99,102,241,0.2)">
    <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: rgba(99,102,241,0.2)">
        <svg class="w-6 h-6" style="color: #6366f1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>
    <div class="flex-1">
        <div class="font-bold" style="color: var(--color-text)">
            {{ $activeSubscription->plan->name }} Plan &bull; {{ ucfirst($activeSubscription->billing_cycle) }}
        </div>
        <div class="text-sm mt-0.5" style="color: var(--color-text-muted)">
            Active until {{ $activeSubscription->end_date->format('F j, Y') }}
            &bull; {{ $daysLeft }} days remaining
        </div>
    </div>
    @if($daysLeft <= 30)
    <a href="{{ route('customer.subscription') }}" class="btn btn-warning text-sm">Renew Now</a>
    @endif
    <div class="text-right">
        <div class="text-2xl font-black" style="color: #6366f1">{{ $activeSubscription->currency ?? 'USD' }} {{ number_format($activeSubscription->amount, 2) }}</div>
        <div class="text-xs" style="color: var(--color-text-muted)">per {{ $activeSubscription->billing_cycle }}</div>
        @if($activeSubscription->is_custom_rate)
        <div class="text-xs mt-1" style="color: var(--color-text-muted)">
            Base {{ $activeSubscription->currency ?? 'USD' }} {{ number_format($activeSubscription->base_amount ?? 0, 2) }}
        </div>
        <div class="text-xs" style="color: #f59e0b">Special price</div>
        @endif
    </div>
</div>
@endif

<!-- Stats Row -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8 stagger-children">
    <div class="card text-center animate-fadeInUp" style="background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.08))">
        <div class="text-3xl font-black mb-1" style="color: #6366f1">{{ $stats['open_tickets'] }}</div>
        <div class="text-sm font-medium" style="color: var(--color-text-muted)">Open Tickets</div>
    </div>
    <div class="card text-center animate-fadeInUp" style="background: linear-gradient(135deg, rgba(245,158,11,0.08), rgba(251,191,36,0.08))">
        <div class="text-3xl font-black mb-1" style="color: #f59e0b">{{ $stats['pending_contracts'] }}</div>
        <div class="text-sm font-medium" style="color: var(--color-text-muted)">Pending Contracts</div>
    </div>
    <div class="card text-center animate-fadeInUp" style="background: linear-gradient(135deg, rgba(239,68,68,0.08), rgba(248,113,113,0.08))">
        <div class="text-3xl font-black mb-1" style="color: #ef4444">{{ $stats['unpaid_invoices'] }}</div>
        <div class="text-sm font-medium" style="color: var(--color-text-muted)">Unpaid Invoices</div>
    </div>
    <div class="card text-center animate-fadeInUp" style="background: linear-gradient(135deg, rgba(16,185,129,0.08), rgba(52,211,153,0.08))">
        <div class="text-3xl font-black mb-1" style="color: #10b981">{{ $stats['signed_contracts'] }}</div>
        <div class="text-sm font-medium" style="color: var(--color-text-muted)">Signed Contracts</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Announcements -->
    <div class="lg:col-span-2 card animate-fadeInUp">
        <h3 class="font-bold text-lg mb-5" style="color: var(--color-text)">
            📢 Announcements
        </h3>
        @forelse($announcements as $ann)
        <div class="announcement-card {{ $ann->type }}">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <div class="font-semibold mb-1" style="color: var(--color-text)">{{ $ann->title }}</div>
                    <p class="text-sm" style="color: var(--color-text-muted)">{{ Str::limit($ann->content, 120) }}</p>
                </div>
                <span class="badge badge-{{ $ann->type === 'success' ? 'success' : ($ann->type === 'warning' ? 'warning' : ($ann->type === 'maintenance' ? 'danger' : 'info')) }} flex-shrink-0">
                    {{ ucfirst($ann->type) }}
                </span>
            </div>
            <div class="text-xs mt-2" style="color: var(--color-text-muted)">{{ $ann->published_at->diffForHumans() }}</div>
        </div>
        @empty
        <div class="text-center py-8" style="color: var(--color-text-muted)">
            <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
            No announcements at this time.
        </div>
        @endforelse
    </div>

    <!-- Quick Actions -->
    <div class="space-y-4">
        <div class="card animate-fadeInUp">
            <h3 class="font-bold text-lg mb-4" style="color: var(--color-text)">Quick Actions</h3>
            <div class="space-y-2">
                <a href="{{ route('customer.tickets.create') }}" class="btn btn-primary w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Raise Support Ticket
                </a>
                <a href="{{ route('customer.contracts') }}" class="btn btn-outline w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    Sign Contracts
                </a>
                <a href="{{ route('customer.invoices') }}" class="btn btn-outline w-full justify-start">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Download Invoices
                </a>
            </div>
        </div>

        @if($pendingContracts->count() > 0)
        <div class="card animate-fadeInUp" style="border: 2px solid rgba(245,158,11,0.3); background: rgba(245,158,11,0.05)">
            <h3 class="font-bold mb-3" style="color: #f59e0b">⚠️ Action Required</h3>
            @foreach($pendingContracts as $contract)
            <a href="{{ route('customer.contracts.show', $contract) }}" class="block p-3 rounded-xl mb-2 transition-all hover:scale-[1.01]"
               style="background: var(--color-surface-alt)">
                <div class="font-medium text-sm" style="color: var(--color-text)">{{ $contract->title }}</div>
                <div class="text-xs mt-0.5" style="color: var(--color-text-muted)">Pending your signature</div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
