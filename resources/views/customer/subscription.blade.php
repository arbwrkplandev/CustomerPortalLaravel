@extends('layouts.app')
@section('title', 'My Subscription')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="mb-8">
        <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">My Subscription</h1>
        <p class="mt-1" style="color: var(--color-text-secondary)">Your plan details and usage</p>
    </div>

    @if(!$activeSubscription)
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">No active subscription</h3>
            <p style="color: var(--color-text-secondary)">Contact your account manager to get started.</p>
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <!-- Current Plan Card -->
                <div class="card" style="border: 1px solid var(--color-brand-primary); background: linear-gradient(135deg, rgba(99,102,241,0.1), rgba(139,92,246,0.05))">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="text-xs font-bold uppercase tracking-wider mb-2" style="color: var(--color-brand-primary)">Current Plan</div>
                            <h2 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $activeSubscription->plan->name }}</h2>
                            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $activeSubscription->plan->description }}</p>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl font-black" style="color: var(--color-brand-primary)">
                                ${{ number_format($activeSubscription->price_paid ?? $activeSubscription->plan->price_monthly, 0) }}
                            </div>
                            <div class="text-sm" style="color: var(--color-text-secondary)">/ {{ $activeSubscription->billing_cycle ?? 'month' }}</div>
                        </div>
                    </div>
                    <div class="mt-6 grid grid-cols-2 gap-4">
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-secondary)">Start Date</div>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $activeSubscription->starts_at ? \Carbon\Carbon::parse($activeSubscription->starts_at)->format('M d, Y') : '—' }}</div>
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-wider mb-1" style="color: var(--color-text-secondary)">Renewal Date</div>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $activeSubscription->ends_at ? \Carbon\Carbon::parse($activeSubscription->ends_at)->format('M d, Y') : 'Ongoing' }}</div>
                        </div>
                    </div>
                    <div class="mt-4">
                        @if($activeSubscription->status === 'active')
                            <span class="badge badge-success">Active</span>
                        @elseif($activeSubscription->status === 'trial')
                            <span class="badge badge-warning">Trial</span>
                        @elseif($activeSubscription->status === 'cancelled')
                            <span class="badge badge-danger">Cancelled</span>
                        @endif
                    </div>
                </div>

                <!-- Plan Features -->
                @if($activeSubscription->plan->features)
                <div class="card">
                    <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Plan Features</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        @foreach($activeSubscription->plan->features as $feature)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 flex-shrink-0" style="color: var(--color-success)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span class="text-sm" style="color: var(--color-text-secondary)">{{ $feature }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar Info -->
            <div class="space-y-4">
                <div class="card">
                    <h3 class="font-bold mb-3" style="color: var(--color-text-primary)">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="{{ route('customer.invoices') }}" class="btn btn-outline w-full justify-center">View Invoices</a>
                        <a href="{{ route('customer.tickets.create') }}" class="btn btn-outline w-full justify-center">Get Support</a>
                    </div>
                </div>
                @if($activeSubscription->ends_at && \Carbon\Carbon::parse($activeSubscription->ends_at)->diffInDays(now()) < 30)
                <div class="card" style="border: 1px solid rgba(245,158,11,0.3); background: rgba(245,158,11,0.05)">
                    <div class="text-amber-400 font-bold mb-2">⚠️ Renewing Soon</div>
                    <p class="text-sm" style="color: var(--color-text-secondary)">Your subscription renews on {{ \Carbon\Carbon::parse($activeSubscription->ends_at)->format('M d, Y') }}. Contact your account manager if you wish to make changes.</p>
                </div>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection
