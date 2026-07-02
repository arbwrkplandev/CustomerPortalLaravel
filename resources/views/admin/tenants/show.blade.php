@extends('layouts.app')
@section('title', $tenant->company_name ?? $tenant->name)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    {{-- Page header --}}
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline">← Customers</a>
        <div class="flex-1">
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">{{ $tenant->company_name ?? $tenant->name }}</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $tenant->contact_email ?? $tenant->email }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.tenants.edit', $tenant) }}" class="btn btn-outline">Edit</a>
            <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                @csrf
                <button type="submit" class="btn {{ $tenant->status === 'active' ? 'btn-warning' : 'btn-success' }}">
                    {{ $tenant->status === 'active' ? 'Deactivate' : 'Activate' }}
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl animate-fadeInUp" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400 font-medium">✓ {{ session('success') }}</p>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-6 p-4 rounded-xl animate-fadeInUp" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
        @foreach($errors->all() as $e)<p class="text-red-400 text-sm">{{ $e }}</p>@endforeach
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- LEFT COLUMN --}}
        <div class="space-y-6">
            {{-- Company Details --}}
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Company Details</h3>
                <div class="space-y-3 text-sm">
                    @if($tenant->phone)
                    <div class="flex justify-between"><span style="color:var(--color-text-secondary)">Phone</span><span style="color:var(--color-text-primary)">{{ $tenant->phone }}</span></div>
                    @endif
                    @if($tenant->city || $tenant->country)
                    <div class="flex justify-between"><span style="color:var(--color-text-secondary)">Location</span><span style="color:var(--color-text-primary)">{{ implode(', ', array_filter([$tenant->city, $tenant->country])) }}</span></div>
                    @endif
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Status</span>
                        <span class="badge {{ $tenant->status === 'active' ? 'badge-success' : ($tenant->status === 'trial' ? 'badge-warning' : 'badge-danger') }}">{{ ucfirst($tenant->status) }}</span>
                    </div>
                    <div class="flex justify-between"><span style="color:var(--color-text-secondary)">Customer since</span><span style="color:var(--color-text-primary)">{{ $tenant->created_at->format('M Y') }}</span></div>
                    @if($tenant->corp_id)
                    <div class="flex justify-between"><span style="color:var(--color-text-secondary)">Corp ID</span><span class="font-mono text-xs" style="color:var(--color-text-primary)">{{ $tenant->corp_id }}</span></div>
                    @endif
                </div>
            </div>

            {{-- ═══════════════════════════════════════════════ --}}
            {{-- SUBSCRIPTION MANAGER (Alpine.js interactive)    --}}
            {{-- ═══════════════════════════════════════════════ --}}
            @php
                $sub   = $tenant->active_subscription ?? $tenant->activeSubscription ?? null;
                $hasSub = !empty($sub) && !empty($sub->plan ?? null);
                $subPlanId    = $hasSub ? ($sub->plan_id ?? null) : null;
                $subCycle     = $hasSub ? ($sub->billing_cycle ?? 'monthly') : 'monthly';
                $subCustom    = $hasSub && $sub->is_custom_rate ? (float)($sub->amount ?? 0) : null;
                $subCurrency  = $hasSub ? ($sub->currency ?? 'USD') : 'USD';
                $plansJson    = $plans->map(fn($p) => [
                    'id'    => $p->id,
                    'name'  => $p->name,
                    'monthly'   => (float)($p->monthly_price ?? 0),
                    'quarterly' => (float)($p->quarterly_price ?? 0),
                    'annual'    => (float)($p->annual_price ?? 0),
                    'features'  => is_array($p->features) ? $p->features : (json_decode($p->features ?? '[]', true) ?: []),
                ])->values()->toJson();
            @endphp

            <div x-data="subscriptionManager({{ $plansJson }}, {{ $subPlanId ?? 'null' }}, '{{ $subCycle }}', {{ $subCustom ?? 'null' }}, '{{ $subCurrency }}')"
                 class="card"
                 style="border: 1px solid rgba(99,102,241,0.2)">

                <h3 class="font-bold mb-4 flex items-center gap-2" style="color:var(--color-text-primary)">
                    <svg class="w-4 h-4" style="color:var(--color-brand-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                    Subscription
                </h3>

                {{-- Tab toggle --}}
                <div class="flex gap-1 mb-4 p-1 rounded-lg" style="background:var(--color-surface-2)">
                    <button type="button" @click="activeTab='current'" :class="activeTab==='current' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600 font-semibold' : ''" class="flex-1 py-1.5 px-2 rounded-md text-xs transition-all" style="color:var(--color-text-primary)">Current Plan</button>
                    <button type="button" @click="activeTab='update'" :class="activeTab==='update' ? 'bg-white dark:bg-gray-700 shadow text-indigo-600 font-semibold' : ''" class="flex-1 py-1.5 px-2 rounded-md text-xs transition-all" style="color:var(--color-text-primary)">Update</button>
                </div>

                {{-- CURRENT PLAN TAB --}}
                <div x-show="activeTab==='current'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                    @if($hasSub)
                    {{-- Gradient plan hero --}}
                    <div class="relative rounded-2xl overflow-hidden mb-4" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%)">
                        <div class="absolute inset-0 opacity-20" style="background: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 80 80%22><circle cx=%2240%22 cy=%2240%22 r=%2235%22 fill=%22none%22 stroke=%22white%22 stroke-width=%221%22 opacity=%220.5%22/><circle cx=%2240%22 cy=%2240%22 r=%2220%22 fill=%22none%22 stroke=%22white%22 stroke-width=%221%22 opacity=%220.3%22/></svg>'); background-size: 120px"></div>
                        <div class="relative p-5">
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <div class="text-white/70 text-xs font-semibold uppercase tracking-widest mb-1">Active Plan</div>
                                    <div class="text-white text-2xl font-black">{{ $sub->plan->name }}</div>
                                </div>
                                @if($sub->is_custom_rate)
                                <span class="px-2 py-1 rounded-full text-xs font-bold animate-pulse" style="background:rgba(251,191,36,0.3); color:#fbbf24; border:1px solid rgba(251,191,36,0.5)">★ Custom Rate</span>
                                @else
                                <span class="px-2 py-1 rounded-full text-xs font-semibold" style="background:rgba(255,255,255,0.15); color:white">Standard</span>
                                @endif
                            </div>
                            <div class="flex items-end gap-2 mb-3">
                                <div class="text-white text-4xl font-black">{{ $sub->currency ?? 'USD' }} {{ number_format($sub->amount ?? 0, 2) }}</div>
                                <div class="text-white/70 text-sm mb-1.5">/ {{ $sub->billing_cycle }}</div>
                            </div>
                            @if($sub->is_custom_rate)
                            <div class="text-white/60 text-xs">Standard rate: {{ $sub->currency ?? 'USD' }} {{ number_format($sub->base_amount ?? 0, 2) }} — Custom override applied</div>
                            @endif
                        </div>
                    </div>

                    {{-- Subscription meta --}}
                    <div class="space-y-2 text-sm mb-4">
                        @if($sub->start_date)
                        <div class="flex justify-between">
                            <span style="color:var(--color-text-secondary)">Started</span>
                            <span style="color:var(--color-text-primary)">{{ \Carbon\Carbon::parse($sub->start_date)->format('M d, Y') }}</span>
                        </div>
                        @endif
                        @if($sub->end_date)
                        @php
                            $daysLeft = max(0, \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($sub->end_date), false));
                            $totalDays = max(1, \Carbon\Carbon::parse($sub->start_date ?? now())->diffInDays(\Carbon\Carbon::parse($sub->end_date)));
                            $pct = min(100, max(0, round((1 - $daysLeft / $totalDays) * 100)));
                        @endphp
                        <div class="flex justify-between">
                            <span style="color:var(--color-text-secondary)">Renews</span>
                            <span style="color:var(--color-text-primary)">{{ \Carbon\Carbon::parse($sub->end_date)->format('M d, Y') }}</span>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span style="color:var(--color-text-secondary)">Period progress</span>
                                <span style="color:var(--color-text-secondary)">{{ $daysLeft }}d left</span>
                            </div>
                            <div class="w-full rounded-full h-2" style="background:var(--color-surface-2)">
                                <div class="h-2 rounded-full transition-all duration-700" style="width:{{ $pct }}%; background: linear-gradient(90deg, #6366f1, #8b5cf6)"></div>
                            </div>
                        </div>
                        @endif
                    </div>

                    {{-- Plan features --}}
                    @php $features = is_array($sub->plan->features) ? $sub->plan->features : (json_decode($sub->plan->features ?? '[]', true) ?: []); @endphp
                    @if(count($features) > 0)
                    <div class="space-y-1.5">
                        <div class="text-xs font-semibold uppercase tracking-wide mb-2" style="color:var(--color-text-secondary)">Included Features</div>
                        @foreach($features as $feat)
                        <div class="flex items-center gap-2 text-xs" style="color:var(--color-text-primary)">
                            <svg class="w-3.5 h-3.5 flex-shrink-0 text-emerald-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            {{ $feat }}
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <button type="button" @click="activeTab='update'" class="mt-4 w-full btn btn-outline text-sm justify-center">Change Plan or Rate →</button>

                    @else
                    {{-- No subscription state --}}
                    <div class="text-center py-8">
                        <div class="w-16 h-16 rounded-2xl mx-auto mb-4 flex items-center justify-center" style="background: rgba(99,102,241,0.1)">
                            <svg class="w-8 h-8" style="color:var(--color-brand-primary)" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                        </div>
                        <p class="text-sm font-semibold mb-1" style="color:var(--color-text-primary)">No active subscription</p>
                        <p class="text-xs mb-4" style="color:var(--color-text-secondary)">Assign a plan to activate this customer's account.</p>
                        <button type="button" @click="activeTab='update'" class="btn btn-primary text-sm">Assign Plan</button>
                    </div>
                    @endif
                </div>

                {{-- UPDATE / ASSIGN TAB --}}
                <div x-show="activeTab==='update'" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">

                    {{-- Use PATCH if subscription exists, POST if not --}}
                    <form method="POST"
                          :action="hasSub ? '{{ route('admin.tenants.update-subscription', $tenant) }}' : '{{ route('admin.tenants.assign-subscription', $tenant) }}'"
                          class="space-y-4">
                        @csrf
                        <template x-if="hasSub"><input type="hidden" name="_method" value="PATCH"></template>

                        {{-- Plan selector cards --}}
                        <div>
                            <label class="form-label text-xs mb-2 block">Select Plan</label>
                            <div class="space-y-2">
                                <template x-for="plan in plans" :key="plan.id">
                                    <label :class="selectedPlanId === plan.id ? 'ring-2 ring-indigo-500' : 'ring-1 ring-transparent hover:ring-indigo-300'"
                                           class="flex items-center gap-3 p-3 rounded-xl cursor-pointer transition-all"
                                           style="background:var(--color-surface-2)">
                                        <input type="radio" name="plan_id" :value="plan.id" x-model.number="selectedPlanId" class="sr-only">
                                        <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                             :style="selectedPlanId === plan.id ? 'background: linear-gradient(135deg,#6366f1,#8b5cf6)' : 'background:var(--color-surface)'">
                                            <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 20 20" x-show="selectedPlanId === plan.id"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="text-sm font-semibold" style="color:var(--color-text-primary)" x-text="plan.name"></div>
                                            <div class="text-xs" style="color:var(--color-text-secondary)" x-text="`$${planPriceForCycle(plan).toFixed(2)} / ${cycle}`"></div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Billing cycle pills --}}
                        <div>
                            <label class="form-label text-xs mb-2 block">Billing Cycle</label>
                            <input type="hidden" name="billing_cycle" :value="cycle">
                            <div class="flex gap-2">
                                <template x-for="c in ['monthly','quarterly','annual']" :key="c">
                                    <button type="button" @click="cycle = c; syncCustomRate()"
                                            :class="cycle === c ? 'ring-2 ring-indigo-500 font-semibold' : 'ring-1'"
                                            class="flex-1 py-2 px-2 rounded-lg text-xs capitalize transition-all ring-transparent"
                                            style="background:var(--color-surface-2); color:var(--color-text-primary)" x-text="c"></button>
                                </template>
                            </div>
                        </div>

                        {{-- Price preview --}}
                        <div class="rounded-xl p-4 text-center" style="background: linear-gradient(135deg, rgba(99,102,241,0.08), rgba(139,92,246,0.08)); border:1px solid rgba(99,102,241,0.2)">
                            <div class="text-xs font-semibold uppercase tracking-wide mb-1" style="color:var(--color-brand-primary)">Plan Price</div>
                            <div class="text-3xl font-black" style="color:var(--color-text-primary)" x-text="selectedPlan ? `${currency} ${planPrice.toFixed(2)}` : '—'"></div>
                            <div class="text-xs mt-0.5" style="color:var(--color-text-secondary)" x-text="`per ${cycle}`"></div>
                        </div>

                        {{-- Custom rate toggle --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="form-label text-xs mb-0">Custom Rate Override</label>
                                <button type="button" @click="useCustomRate = !useCustomRate; if(!useCustomRate) customRate = null"
                                        :class="useCustomRate ? 'bg-indigo-500' : 'bg-gray-400'"
                                        class="relative inline-flex h-5 w-9 rounded-full transition-colors">
                                    <span :class="useCustomRate ? 'translate-x-4' : 'translate-x-0.5'" class="inline-block h-4 w-4 mt-0.5 rounded-full bg-white transition-transform shadow"></span>
                                </button>
                            </div>
                            <div x-show="useCustomRate" x-transition class="space-y-2">
                                <div class="flex gap-2 items-center">
                                    <input type="text" name="currency" x-model="currency" class="form-input w-20 text-center font-mono uppercase" maxlength="3" placeholder="USD">
                                    <input type="number" name="custom_rate" step="0.01" min="0" x-model="customRate" class="form-input flex-1" placeholder="Enter custom amount">
                                </div>
                                <p class="text-xs" style="color:var(--color-text-secondary)">
                                    Override the standard plan rate. Leave empty to revert to plan default.
                                </p>
                            </div>
                            <input x-show="!useCustomRate" type="hidden" name="custom_rate" value="">
                            <input type="hidden" name="currency" :value="useCustomRate ? '' : currency">
                        </div>

                        {{-- Notes --}}
                        <div>
                            <label class="form-label text-xs">Notes (optional)</label>
                            <textarea name="notes" rows="2" class="form-input" placeholder="Reason for custom pricing, contract terms…"></textarea>
                        </div>

                        {{-- Summary badge --}}
                        <div x-show="selectedPlan" class="rounded-lg p-3 text-sm" style="background:var(--color-surface-2)">
                            <span style="color:var(--color-text-secondary)">Final amount: </span>
                            <span class="font-bold" style="color:var(--color-text-primary)" x-text="finalAmount()"></span>
                            <span x-show="useCustomRate && customRate" class="ml-2 text-xs font-semibold text-amber-500">★ Custom</span>
                        </div>

                        <button type="submit" :disabled="!selectedPlanId"
                                class="btn btn-primary w-full justify-center disabled:opacity-50 disabled:cursor-not-allowed"
                                x-text="hasSub ? 'Update Subscription' : 'Assign Plan'"></button>
                    </form>
                </div>
            </div>
        </div>

        {{-- RIGHT COLUMNS --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Users --}}
            <div class="card">
                <h3 class="font-bold mb-5" style="color: var(--color-text-primary)">Users ({{ $tenant->users->count() }})</h3>
                @if($tenant->users->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-secondary)">No users yet.</p>
                @else
                <div class="space-y-4">
                    @foreach($tenant->users as $user)
                    <div class="rounded-2xl p-4" style="background: var(--color-surface-2); border: 1px solid rgba(99,102,241,0.1)">
                        {{-- User info row --}}
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                                 style="background: linear-gradient(135deg,#6366f1,#8b5cf6)">
                                {{ strtoupper(substr($user->name ?? 'U', 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-sm" style="color: var(--color-text-primary)">{{ $user->name }}</div>
                                <div class="text-xs truncate" style="color: var(--color-text-secondary)">{{ $user->email }}</div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="badge" style="background: rgba(99,102,241,0.12); color:#6366f1; font-size:0.65rem">{{ ucfirst($user->role) }}</span>
                                <span class="badge {{ $user->is_active ? 'badge-success' : 'badge-danger' }}" style="font-size:0.65rem">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                            </div>
                        </div>
                        {{-- Reset password form --}}
                        <form method="POST" action="{{ route('admin.tenants.reset-password', [$tenant, $user]) }}"
                              x-data="{ show1: false, show2: false }">
                            @csrf
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">New Password</label>
                                    <div class="relative">
                                        <input :type="show1 ? 'text' : 'password'" name="new_password"
                                               class="form-input pr-10 text-sm w-full"
                                               style="color: var(--color-text-primary); background: var(--color-surface)"
                                               placeholder="Min. 8 characters" required minlength="8">
                                        <button type="button" @click="show1=!show1"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center"
                                                style="color: var(--color-text-secondary)">
                                            <svg x-show="!show1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="show1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium mb-1.5" style="color: var(--color-text-secondary)">Confirm Password</label>
                                    <div class="relative">
                                        <input :type="show2 ? 'text' : 'password'" name="new_password_confirmation"
                                               class="form-input pr-10 text-sm w-full"
                                               style="color: var(--color-text-primary); background: var(--color-surface)"
                                               placeholder="Repeat password" required minlength="8">
                                        <button type="button" @click="show2=!show2"
                                                class="absolute inset-y-0 right-0 px-3 flex items-center"
                                                style="color: var(--color-text-secondary)">
                                            <svg x-show="!show2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            <svg x-show="show2" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <button type="submit"
                                    class="w-full sm:w-auto flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white transition-all hover:opacity-90 active:scale-95"
                                    style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                                Reset Password
                            </button>
                        </form>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Recent Invoices --}}
            <div class="card">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold" style="color: var(--color-text-primary)">Recent Invoices</h3>
                    <a href="{{ route('admin.invoices.index') }}?tenant_id={{ $tenant->id }}" class="text-sm" style="color: var(--color-brand-primary)">View all →</a>
                </div>
                @if($tenant->invoices->isEmpty())
                    <p class="text-sm" style="color: var(--color-text-secondary)">No invoices yet.</p>
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

@push('scripts')
<script>
function subscriptionManager(plans, currentPlanId, currentCycle, currentCustomRate, currentCurrency) {
    return {
        plans: plans,
        activeTab: currentPlanId ? 'current' : 'update',
        hasSub: !!currentPlanId,
        selectedPlanId: currentPlanId,
        cycle: currentCycle || 'monthly',
        useCustomRate: currentCustomRate !== null,
        customRate: currentCustomRate,
        currency: currentCurrency || 'USD',

        get selectedPlan() {
            return this.plans.find(p => p.id === this.selectedPlanId) || null;
        },
        get planPrice() {
            if (!this.selectedPlan) return 0;
            return this.planPriceForCycle(this.selectedPlan);
        },
        planPriceForCycle(plan) {
            return plan[this.cycle] ?? plan.monthly ?? 0;
        },
        syncCustomRate() {
            if (this.useCustomRate && !this.customRate) {
                this.customRate = this.planPrice.toFixed(2);
            }
        },
        finalAmount() {
            if (!this.selectedPlan) return '—';
            const amt = this.useCustomRate && this.customRate ? parseFloat(this.customRate) : this.planPrice;
            return `${this.currency} ${isNaN(amt) ? '0.00' : amt.toFixed(2)} / ${this.cycle}`;
        }
    };
}
</script>
@endpush
