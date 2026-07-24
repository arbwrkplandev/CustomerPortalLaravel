@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Customers')
@section('page-subtitle', 'Manage all tenant accounts')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<!-- Header Actions -->
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex gap-2 flex-1 max-w-lg">
        <label class="company-search" for="companySearchInput">
            <svg class="company-search__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input id="companySearchInput" type="text" name="search" value="{{ request('search') }}" placeholder="Search by company name..." class="form-input company-search__input flex-1" autocomplete="off">
            <span class="company-search__glow" aria-hidden="true"></span>
        </label>
        <select name="status" class="form-input w-36">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="trial" @selected(request('status') === 'trial')>Trial</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
    <div id="companySearchMeta" class="company-search-meta">Type to instantly find a company</div>
    <!-- <a href="{{ route('admin.tenants.create') }}" class="btn btn-primary">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        New Customer
    </a> -->
</div>

<!-- Table -->
<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Subscription</th>
                    <th>Billing Cycle</th>
                    <th>Joined</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="customersTableBody">
                @forelse($tenants as $tenant)
                <tr class="animate-fadeInUp">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #6366f1, #8b5cf6)">
                                 {{ Str::upper(collect(explode(' ', $tenant->company_name))->map(fn($word) => $word[0] ?? '')->implode('')) }}
                            </div>
                            <div>
                                <div class="font-semibold" style="color: var(--color-text)">{{ $tenant->company_name }}</div>
                                <div class="font-semibold" style="color: var(--color-text)">{{ $tenant->company_code }}</div>
                                <div class="text-xs" style="color: var(--color-text-muted)">{{ $tenant->city }}, {{ $tenant->country }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-medium" style="color: var(--color-text)">{{ $tenant->contact_name }}</div>
                        <div class="text-xs" style="color: var(--color-text-muted)">{{ $tenant->contact_email }}</div>
                    </td>
                    <td>
                        <span class="badge badge-{{ $tenant->status === 'active' ? 'success' : ($tenant->status === 'trial' ? 'warning' : ($tenant->status === 'suspended' ? 'danger' : 'secondary')) }}">
                            {{ ucfirst($tenant->status) }}
                        </span>
                        <div class="mt-1 text-xs" style="color: var(--color-text-muted)">
                            @if(($tenant->agreement_sent_count ?? 0) > 0)
                                <span style="color: #67e8f9">Agreements: {{ $tenant->agreement_signed_count ?? 0 }}/{{ $tenant->agreement_sent_count ?? 0 }} signed</span>
                            @else
                                <span>Agreements: none sent</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold" style="background: rgba(99,102,241,0.12); color: #6366f1; border: 1px solid rgba(99,102,241,0.25)">
                                <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Enterprise
                            </span>
                        </div>
                        <div class="text-xs mt-0.5" style="color:var(--color-text-muted)">
                            USD 499/monthly
                        </div>
                    </td>
                    <td class="text-sm capitalize" style="color: var(--color-text-muted)">
                        <span class="inline-flex items-center gap-1">
                            <span style="color:#6366f1">●</span>
                            Monthly
                        </span>
                    </td>
                    <td class="text-sm" style="color: var(--color-text-muted)">
                        {{ $tenant->created_at->format('M d, Y') }}
                    </td>
                    <td>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="btn btn-primary py-1.5 px-3 text-xs js-customer-view"
                                data-details-url="{{ route('admin.tenants.details', $tenant->id) }}"
                                data-workspace-url="{{ route('admin.tenants.agreements.workspace', $tenant->id) }}"
                                data-template-base-url="{{ url('/admin/customers/' . $tenant->id . '/agreements/template') }}"
                                data-draft-url="{{ route('admin.tenants.agreements.draft', $tenant->id) }}"
                                data-send-url="{{ route('admin.tenants.agreements.send', $tenant->id) }}"
                                data-signed-url="{{ route('admin.tenants.agreements.signed', $tenant->id) }}"
                                data-customer-name="{{ $tenant->company_name }}"
                            >
                                View
                            </button>
                            {{-- <form method="POST" action="{{ route('admin.tenants.toggle-status', $tenant) }}">
                                @csrf
                                <button type="submit" class="btn {{ $tenant->status === 'active' ? 'btn-warning' : 'btn-success' }} py-1.5 px-3 text-xs">
                                    {{ $tenant->status === 'active' ? 'Deactivate' : 'Activate' }}
                                </button>
                            </form> --}}
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-12" style="color: var(--color-text-muted)">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16"/></svg>
                        No customers found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex items-center justify-between px-2">
        <p class="text-sm" style="color: var(--color-text-muted)">
            Showing {{ $tenants->firstItem() }}–{{ $tenants->lastItem() }} of {{ $tenants->total() }} customers
        </p>
        {{ $tenants->links() }}
    </div>
</div>

<div id="customerDetailsModal" class="customer-modal" aria-hidden="true">
    <div class="customer-modal__backdrop" data-close-customer-modal></div>
    <div class="customer-modal__panel" role="dialog" aria-modal="true" aria-labelledby="customerDetailsTitle">
        <button class="customer-modal__close" type="button" aria-label="Close" data-close-customer-modal>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div id="customerModalBody" class="customer-modal__content">
            <div class="customer-modal__loading">
                <span class="customer-pulse-dot"></span>
                <span>Loading premium customer profile...</span>
            </div>
        </div>
    </div>
</div>

<div id="customerAgreementModal" class="agreement-modal" aria-hidden="true">
    <div class="agreement-modal__backdrop" data-close-agreement-modal></div>
    <div class="agreement-modal__panel" role="dialog" aria-modal="true" aria-labelledby="agreementModalTitle">
        <button class="agreement-modal__close" type="button" aria-label="Close" data-close-agreement-modal>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>

        <div id="agreementModalBody" class="agreement-modal__content">
            <div class="agreement-modal__loading">
                <span class="customer-pulse-dot"></span>
                <span>Preparing agreement workspace...</span>
            </div>
        </div>
    </div>
</div>

<div id="customerSignedModal" class="signed-modal" aria-hidden="true">
    <div class="signed-modal__backdrop" data-close-signed-modal></div>
    <div class="signed-modal__panel" role="dialog" aria-modal="true" aria-labelledby="signedModalTitle">
        <button class="signed-modal__close" type="button" aria-label="Close" data-close-signed-modal>
            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div id="signedModalBody" class="signed-modal__content">
            <div class="agreement-modal__loading">
                <span class="customer-pulse-dot"></span>
                <span>Loading signed agreement copies...</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .company-search {
        position: relative;
        display: flex;
        align-items: center;
        min-width: 280px;
        flex: 1 1 auto;
    }

    .company-search__icon {
        position: absolute;
        left: 0.72rem;
        width: 1rem;
        height: 1rem;
        color: #6366f1;
        z-index: 2;
        pointer-events: none;
    }

    .company-search__input {
        padding-left: 2.25rem;
        border-radius: 12px;
        border: 1px solid rgba(99, 102, 241, 0.22);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(244, 247, 255, 0.96));
        transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
    }

    .company-search__input:focus {
        border-color: rgba(79, 70, 229, 0.55);
        box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.12);
        transform: translateY(-1px);
    }

    .company-search__glow {
        position: absolute;
        right: 0.7rem;
        width: 0.5rem;
        height: 0.5rem;
        border-radius: 999px;
        background: rgba(99, 102, 241, 0.35);
        box-shadow: 0 0 0 rgba(99, 102, 241, 0.46);
        animation: companySearchPulse 1.6s infinite;
        pointer-events: none;
    }

    .company-search-meta {
        font-size: 0.76rem;
        font-weight: 600;
        color: #6366f1;
        background: rgba(99, 102, 241, 0.08);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 999px;
        padding: 0.34rem 0.7rem;
    }

    .company-row-hidden {
        opacity: 0;
        transform: translateY(4px) scale(0.995);
        transition: opacity 180ms ease, transform 180ms ease;
    }

    .company-row-visible {
        opacity: 1;
        transform: translateY(0) scale(1);
        transition: opacity 180ms ease, transform 180ms ease;
    }

    @keyframes companySearchPulse {
        0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.45); }
        70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
        100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
    }

    .customer-modal {
        position: fixed;
        inset: 0;
        z-index: 120;
        display: grid;
        place-items: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 220ms ease;
    }

    .customer-modal.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .customer-modal__backdrop {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 15% 15%, rgba(66, 33, 138, 0.32), transparent 30%),
                    radial-gradient(circle at 85% 70%, rgba(0, 170, 255, 0.25), transparent 30%),
                    rgba(8, 10, 22, 0.66);
        backdrop-filter: blur(9px);
    }

    .customer-modal__panel {
        position: relative;
        width: min(980px, calc(100vw - 2rem));
        max-height: calc(100vh - 2rem);
        overflow: auto;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: linear-gradient(140deg, rgba(26, 17, 62, 0.95) 0%, rgba(16, 26, 70, 0.95) 45%, rgba(8, 55, 92, 0.92) 100%);
        box-shadow: 0 25px 60px rgba(6, 10, 28, 0.65);
        transform: translateY(20px) scale(0.98);
        transition: transform 260ms ease;
        color: #e7ecff;
    }

    .customer-modal.is-open .customer-modal__panel {
        transform: translateY(0) scale(1);
    }

    .customer-modal__close {
        position: sticky;
        top: 1rem;
        float: right;
        margin: 1rem 1rem 0 0;
        z-index: 2;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: #f6f7ff;
    }

    .customer-modal__content {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .customer-modal__hero {
        position: relative;
        border-radius: 18px;
        padding: 1.25rem;
        background: linear-gradient(145deg, rgba(121, 87, 255, 0.33), rgba(0, 209, 255, 0.23));
        border: 1px solid rgba(255, 255, 255, 0.22);
        overflow: hidden;
    }

    .customer-modal__hero::after {
        content: '';
        position: absolute;
        right: -40px;
        top: -40px;
        width: 180px;
        height: 180px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.25), transparent 65%);
    }

    .customer-grid {
        margin-top: 1rem;
        display: grid;
        gap: 0.75rem;
        grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    }

    .customer-stat {
        border-radius: 14px;
        padding: 0.7rem 0.75rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        animation: cardReveal 380ms ease both;
    }

    .customer-stat__label {
        display: block;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.72;
    }

    .customer-stat__value {
        margin-top: 0.2rem;
        font-weight: 700;
        font-size: 0.95rem;
    }

    .customer-section {
        margin-top: 1rem;
        border-radius: 16px;
        padding: 0.95rem;
        background: rgba(7, 18, 46, 0.44);
        border: 1px solid rgba(157, 207, 255, 0.22);
    }

    .customer-contact {
        margin-top: 0.7rem;
        border-radius: 14px;
        padding: 0.8rem;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.16);
        animation: cardReveal 400ms ease both;
    }

    .customer-contact small {
        opacity: 0.82;
    }

    .customer-address {
        margin-top: 0.55rem;
        border-radius: 10px;
        padding: 0.6rem 0.7rem;
        background: rgba(6, 12, 32, 0.6);
        border: 1px dashed rgba(165, 213, 255, 0.35);
        font-size: 0.86rem;
    }

    .customer-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.55rem;
        border-radius: 999px;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .customer-pill.active {
        color: #0f915f;
        background: rgba(98, 255, 177, 0.27);
    }

    .customer-pill.inactive {
        color: #8f9ab6;
        background: rgba(255, 255, 255, 0.18);
    }

    .customer-modal__loading {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        font-weight: 600;
    }

    .customer-action-hub {
        margin-top: 1rem;
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: linear-gradient(135deg, rgba(56, 189, 248, 0.16), rgba(129, 140, 248, 0.16), rgba(244, 114, 182, 0.12));
        padding: 0.95rem;
        animation: cardReveal 460ms ease both;
    }

    .customer-action-hub__title {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        opacity: 0.82;
        font-weight: 700;
    }

    .customer-action-grid {
        margin-top: 0.7rem;
        display: grid;
        gap: 0.65rem;
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .customer-action-btn {
        position: relative;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: rgba(5, 10, 30, 0.42);
        color: #f8fafc;
        padding: 0.7rem 0.8rem;
        text-align: left;
        transition: transform 180ms ease, border-color 180ms ease, box-shadow 180ms ease;
    }

    .customer-action-btn:hover {
        transform: translateY(-1px);
        border-color: rgba(125, 211, 252, 0.85);
        box-shadow: 0 8px 26px rgba(56, 189, 248, 0.26);
    }

    .customer-action-btn strong {
        display: block;
        font-size: 0.9rem;
    }

    .customer-action-btn span {
        display: block;
        margin-top: 0.15rem;
        font-size: 0.72rem;
        opacity: 0.86;
    }

    @media (max-width: 760px) {
        .customer-action-grid {
            grid-template-columns: 1fr;
        }
    }

    .customer-pulse-dot {
        width: 10px;
        height: 10px;
        border-radius: 999px;
        background: #78d6ff;
        box-shadow: 0 0 0 rgba(120, 214, 255, 0.7);
        animation: pulseDot 1.2s infinite;
    }

    @keyframes cardReveal {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes pulseDot {
        0% { box-shadow: 0 0 0 0 rgba(120, 214, 255, 0.7); }
        70% { box-shadow: 0 0 0 12px rgba(120, 214, 255, 0); }
        100% { box-shadow: 0 0 0 0 rgba(120, 214, 255, 0); }
    }

    .agreement-modal {
        position: fixed;
        inset: 0;
        z-index: 140;
        display: grid;
        place-items: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 220ms ease;
    }

    .agreement-modal.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .agreement-modal__backdrop {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 15% 20%, rgba(253, 186, 116, 0.24), transparent 30%),
                    radial-gradient(circle at 88% 75%, rgba(244, 114, 182, 0.24), transparent 28%),
                    rgba(6, 8, 30, 0.72);
        backdrop-filter: blur(8px);
    }

    .agreement-modal__panel {
        position: relative;
        width: min(1220px, calc(100vw - 1.8rem));
        max-height: calc(100vh - 1.5rem);
        overflow: auto;
        border-radius: 26px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: linear-gradient(145deg, rgba(31, 16, 68, 0.95) 0%, rgba(12, 48, 92, 0.95) 55%, rgba(5, 78, 114, 0.92) 100%);
        box-shadow: 0 25px 90px rgba(5, 10, 28, 0.76);
        transform: translateY(18px) scale(0.98);
        transition: transform 240ms ease;
        color: #e8ecff;
    }

    .agreement-modal.is-open .agreement-modal__panel {
        transform: translateY(0) scale(1);
    }

    .agreement-modal__close {
        position: sticky;
        top: 1rem;
        float: right;
        margin: 1rem 1rem 0 0;
        z-index: 4;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.36);
        background: rgba(255, 255, 255, 0.15);
        color: #ffffff;
    }

    .agreement-modal__content {
        padding: 1.25rem 1.4rem 1.5rem;
    }

    .agreement-modal__loading {
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        font-weight: 600;
    }

    .agreement-hero {
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: linear-gradient(135deg, rgba(253, 186, 116, 0.21), rgba(129, 140, 248, 0.22), rgba(34, 211, 238, 0.16));
        padding: 1rem 1.1rem;
        margin-bottom: 0.95rem;
    }

    .agreement-grid {
        display: grid;
        gap: 1rem;
        grid-template-columns: 320px minmax(0, 1fr);
    }

    .agreement-card {
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(4, 17, 53, 0.44);
        padding: 0.9rem;
    }

    .agreement-item {
        width: 100%;
        text-align: left;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        padding: 0.65rem 0.7rem;
        background: rgba(255, 255, 255, 0.08);
        color: #eef2ff;
        transition: all 180ms ease;
    }

    .agreement-item:hover {
        border-color: rgba(196, 181, 253, 0.6);
        transform: translateY(-1px);
    }

    .agreement-item.active {
        border-color: rgba(34, 211, 238, 0.7);
        background: linear-gradient(140deg, rgba(56, 189, 248, 0.22), rgba(167, 139, 250, 0.22));
        box-shadow: 0 0 0 1px rgba(56, 189, 248, 0.4) inset;
    }

    .merge-chip {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        padding: 0.25rem 0.65rem;
        background: rgba(255, 255, 255, 0.08);
        color: #e2e8f0;
        font-size: 0.75rem;
        font-weight: 700;
        cursor: pointer;
    }

    .merge-chip:hover {
        border-color: rgba(147, 197, 253, 0.7);
        color: #bfdbfe;
    }

    .agreement-editor {
        width: 100%;
        min-height: 360px;
        max-height: 52vh;
        overflow-y: auto;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.24);
        background: rgba(255, 255, 255, 0.94);
        color: #111827;
        padding: 0.85rem;
        line-height: 1.6;
        font-size: 0.92rem;
    }

    .agreement-editor:focus {
        outline: 2px solid rgba(56, 189, 248, 0.42);
        outline-offset: 1px;
    }

    .agreement-editor:empty::before {
        content: attr(data-placeholder);
        color: #6b7280;
    }

    .agreement-editor-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        margin-bottom: 0.65rem;
        padding: 0.55rem;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        background: rgba(255, 255, 255, 0.1);
    }

    .agreement-editor-tool {
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.15);
        color: #e2e8f0;
        border-radius: 8px;
        padding: 0.3rem 0.5rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .agreement-editor-tool.select {
        min-width: 90px;
    }

    .timeline-row {
        border: 1px solid rgba(255, 255, 255, 0.16);
        border-radius: 12px;
        padding: 0.6rem 0.75rem;
        background: rgba(255, 255, 255, 0.06);
    }

    @media (max-width: 980px) {
        .agreement-grid {
            grid-template-columns: 1fr;
        }
    }

    .signed-modal {
        position: fixed;
        inset: 0;
        z-index: 150;
        display: grid;
        place-items: center;
        opacity: 0;
        pointer-events: none;
        transition: opacity 220ms ease;
    }

    .signed-modal.is-open {
        opacity: 1;
        pointer-events: auto;
    }

    .signed-modal__backdrop {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 18% 18%, rgba(250, 204, 21, 0.2), transparent 26%),
                    radial-gradient(circle at 85% 70%, rgba(34, 211, 238, 0.24), transparent 28%),
                    rgba(6, 9, 28, 0.74);
        backdrop-filter: blur(8px);
    }

    .signed-modal__panel {
        position: relative;
        width: min(1180px, calc(100vw - 2rem));
        max-height: calc(100vh - 2rem);
        overflow: auto;
        border-radius: 24px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: linear-gradient(145deg, rgba(37, 19, 88, 0.95) 0%, rgba(13, 45, 86, 0.95) 55%, rgba(8, 68, 86, 0.93) 100%);
        box-shadow: 0 28px 100px rgba(4, 10, 30, 0.82);
        transform: translateY(18px) scale(0.98);
        transition: transform 230ms ease;
        color: #f8fafc;
    }

    .signed-modal.is-open .signed-modal__panel {
        transform: translateY(0) scale(1);
    }

    .signed-modal__close {
        position: sticky;
        top: 1rem;
        float: right;
        margin: 1rem 1rem 0 0;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.4);
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
        z-index: 5;
    }

    .signed-modal__content {
        padding: 1.25rem 1.5rem 1.5rem;
    }

    .signed-shell {
        display: grid;
        gap: 1rem;
        grid-template-columns: 320px minmax(0, 1fr);
    }

    .signed-card {
        border-radius: 16px;
        border: 1px solid rgba(255, 255, 255, 0.16);
        background: rgba(7, 21, 54, 0.5);
        padding: 0.9rem;
    }

    .signed-item {
        width: 100%;
        text-align: left;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.14);
        background: rgba(255, 255, 255, 0.08);
        padding: 0.65rem 0.75rem;
        color: #ecfeff;
        transition: all 180ms ease;
    }

    .signed-item:hover {
        border-color: rgba(56, 189, 248, 0.7);
        transform: translateY(-1px);
    }

    .signed-item.active {
        border-color: rgba(129, 140, 248, 0.8);
        background: linear-gradient(140deg, rgba(56, 189, 248, 0.2), rgba(129, 140, 248, 0.26));
    }

    .signed-content-preview {
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        background: rgba(255, 255, 255, 0.92);
        color: #111827;
        padding: 1rem;
        max-height: 54vh;
        overflow-y: auto;
    }

    .signed-signature {
        max-height: 110px;
        background: white;
        border: 1px solid rgba(148, 163, 184, 0.45);
        border-radius: 8px;
        padding: 8px;
    }

    @media (max-width: 960px) {
        .signed-shell {
            grid-template-columns: 1fr;
        }

        .company-search-meta {
            width: 100%;
            text-align: center;
        }
    }
</style>
@endpush

@push('scripts')
<script>
    (() => {
        const input = document.getElementById('companySearchInput');
        const tbody = document.getElementById('customersTableBody');
        const meta = document.getElementById('companySearchMeta');

        if (!input || !tbody) {
            return;
        }

        const rows = Array.from(tbody.querySelectorAll('tr'));
        const dataRows = rows.filter((row) => !row.querySelector('td[colspan]'));
        const emptyRow = rows.find((row) => row.querySelector('td[colspan]')) || null;

        const customNoMatchRow = document.createElement('tr');
        customNoMatchRow.innerHTML = `
            <td colspan="7" class="text-center py-10" style="color: var(--color-text-muted)">
                <div class="text-sm font-semibold">No company matched your search.</div>
                <div class="text-xs mt-1 opacity-80">Try a different company name.</div>
            </td>
        `;

        const normalize = (value) => String(value || '').toLowerCase().trim();

        const companyNameForRow = (row) => {
            const companyCell = row.querySelector('td:first-child .font-semibold');
            return normalize(companyCell?.textContent || '');
        };

        const setMeta = (query, visible, total) => {
            if (!meta) {
                return;
            }

            if (query === '') {
                meta.textContent = `Showing ${visible} of ${total} companies`;
                return;
            }

            meta.textContent = `${visible} match${visible === 1 ? '' : 'es'} for "${query}"`;
        };

        const applyFilter = () => {
            const query = normalize(input.value);
            let visibleCount = 0;

            dataRows.forEach((row) => {
                const matched = query === '' || companyNameForRow(row).includes(query);
                row.classList.toggle('company-row-hidden', !matched);
                row.classList.toggle('company-row-visible', matched);
                row.style.display = matched ? '' : 'none';
                if (matched) {
                    visibleCount++;
                }
            });

            if (emptyRow) {
                emptyRow.style.display = dataRows.length === 0 ? '' : 'none';
            }

            if (dataRows.length > 0 && visibleCount === 0) {
                if (!tbody.contains(customNoMatchRow)) {
                    tbody.appendChild(customNoMatchRow);
                }
            } else if (tbody.contains(customNoMatchRow)) {
                customNoMatchRow.remove();
            }

            setMeta(input.value.trim(), visibleCount, dataRows.length);
        };

        input.addEventListener('input', applyFilter);
        applyFilter();
    })();

    (() => {
        const modal = document.getElementById('customerDetailsModal');
        const body = document.getElementById('customerModalBody');
        const viewButtons = document.querySelectorAll('.js-customer-view');

        const toText = (value, fallback = 'N/A') => {
            if (value === null || value === undefined) return fallback;
            const text = String(value).trim();
            return text === '' ? fallback : text;
        };

        const escapeHtml = (unsafe) => String(unsafe)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const statusClass = (status) => String(status).toLowerCase() === 'active' ? 'active' : 'inactive';

        const customerInitials = (name) => {
            const parts = String(name || '').trim().split(/\s+/).filter(Boolean);
            if (!parts.length) return 'CU';
            return parts.slice(0, 2).map(part => part[0]).join('').toUpperCase();
        };

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const renderDetails = (payload, actionConfig = {}) => {
            const customer = payload.customer || {};
            const contacts = Array.isArray(customer.contacts) ? customer.contacts : [];
            const createdAt = toText(customer.created_at, 'Unknown date').replaceAll('/', '-');
            const customerName = toText(customer.company_name, toText(actionConfig.customerName, 'Customer'));

            const contactMarkup = contacts.map((entry, index) => {
                // Backward compatibility:
                // - old format: { contact: {...}, addresses: [...] }
                // - new format: { contact_name: ..., addresses: [...] }
                const contact = (entry && typeof entry === 'object' && entry.contact && typeof entry.contact === 'object')
                    ? entry.contact
                    : (entry || {});
                const addresses = Array.isArray(entry.addresses) ? entry.addresses : [];

                const addressesMarkup = addresses.length
                    ? addresses.map((address) => `
                        <div class="customer-address">
                            <div>${escapeHtml(toText(address.address_line1))}, ${escapeHtml(toText(address.address_line2, ''))} ${escapeHtml(toText(address.address_line3, ''))}</div>
                            <div>${escapeHtml(toText(address.address_city))}, ${escapeHtml(toText(address.state))} ${escapeHtml(toText(address.zip_code))}</div>
                            <div>${escapeHtml(toText(address.address_country))} ${toText(address.is_default, '').toLowerCase() === 'yes' ? '• Default' : ''}</div>
                        </div>
                    `).join('')
                    : '<div class="customer-address">No address available</div>';

                return `
                    <article class="customer-contact" style="animation-delay:${Math.min(index * 60, 480)}ms">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h4 class="font-bold text-sm">${escapeHtml(toText(contact.contact_name, 'Unnamed Contact'))}</h4>
                            <span class="customer-pill ${statusClass(contact.contact_status)}">${escapeHtml(toText(contact.contact_status, 'Unknown'))}</span>
                        </div>
                        <small>${escapeHtml(toText(contact.contact_title, 'No title'))} • ${escapeHtml(toText(contact.contact_type, 'N/A'))}</small>
                        <div class="mt-2 text-sm">📧 ${escapeHtml(toText(contact.contact_email))}</div>
                        <div class="text-sm">📞 ${escapeHtml(toText(contact.contact_phone))}</div>
                        ${addressesMarkup}
                    </article>
                `;
            }).join('');

            body.innerHTML = `
                <section class="customer-modal__hero">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-14 h-14 rounded-2xl flex items-center justify-center text-xl font-black"
                                 style="background: rgba(255,255,255,0.22); border: 1px solid rgba(255,255,255,0.38)">
                                ${escapeHtml(customerInitials(customerName))}
                            </div>
                            <div>
                                <h2 id="customerDetailsTitle" class="text-xl font-black">${escapeHtml(customerName)}</h2>
                                <div class="text-sm opacity-80">Code: ${escapeHtml(toText(customer.customer_code, toText(customer.vendor_code)))}</div>
                            </div>
                        </div>
                        <span class="customer-pill ${statusClass(customer.status)}">${escapeHtml(toText(customer.status, 'Unknown'))}</span>
                    </div>

                    <div class="customer-grid">
                        <div class="customer-stat" style="animation-delay:70ms">
                            <span class="customer-stat__label">City</span>
                            <span class="customer-stat__value">${escapeHtml(toText(customer.city))}</span>
                        </div>
                        <div class="customer-stat" style="animation-delay:120ms">
                            <span class="customer-stat__label">Country</span>
                            <span class="customer-stat__value">${escapeHtml(toText(customer.country))}</span>
                        </div>
                        <div class="customer-stat" style="animation-delay:170ms">
                            <span class="customer-stat__label">Created</span>
                            <span class="customer-stat__value">${escapeHtml(createdAt)}</span>
                        </div>
                        <div class="customer-stat" style="animation-delay:220ms">
                            <span class="customer-stat__label">Type</span>
                            <span class="customer-stat__value">${escapeHtml(toText(customer.customer_type))}</span>
                        </div>
                        <div class="customer-stat" style="animation-delay:270ms">
                            <span class="customer-stat__label">Created By</span>
                            <span class="customer-stat__value">${escapeHtml(toText(customer.created_by))}</span>
                        </div>
                        <div class="customer-stat" style="animation-delay:320ms">
                            <span class="customer-stat__label">Website</span>
                            <span class="customer-stat__value">${escapeHtml(toText(customer.website))}</span>
                        </div>
                    </div>
                </section>

                <section class="customer-section">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="font-bold text-base">Contact & Address Network</h3>
                        <span class="text-xs opacity-80">${contacts.length} contacts</span>
                    </div>
                    ${contactMarkup || '<div class="customer-address">No contact data available.</div>'}
                </section>

                <section class="customer-action-hub">
                    <div class="customer-action-hub__title">Agreement Operations</div>
                    <div class="customer-action-grid">
                        <button
                            type="button"
                            class="customer-action-btn js-tag-agreement"
                            data-workspace-url="${escapeHtml(actionConfig.workspaceUrl || '')}"
                            data-template-base-url="${escapeHtml(actionConfig.templateBaseUrl || '')}"
                            data-draft-url="${escapeHtml(actionConfig.draftUrl || '')}"
                            data-send-url="${escapeHtml(actionConfig.sendUrl || '')}"
                            data-customer-name="${escapeHtml(actionConfig.customerName || customerName)}"
                        >
                            <strong>Tag Agreement</strong>
                            <span>Open animated workspace, edit, draft, and send.</span>
                        </button>
                        <button
                            type="button"
                            class="customer-action-btn js-view-signed"
                            data-signed-url="${escapeHtml(actionConfig.signedUrl || '')}"
                            data-customer-name="${escapeHtml(actionConfig.customerName || customerName)}"
                        >
                            <strong>Signed Copies</strong>
                            <span>Review signed contracts, preview signatures, and download.</span>
                        </button>
                    </div>
                </section>
            `;
        };

        const renderError = (message) => {
            body.innerHTML = `
                <div class="customer-modal__loading" style="min-height:240px; flex-direction:column; text-align:center;">
                    <div class="text-3xl">⚠</div>
                    <div>${escapeHtml(message || 'Could not load customer profile.')}</div>
                </div>
            `;
        };

        const setLoading = () => {
            body.innerHTML = `
                <div class="customer-modal__loading">
                    <span class="customer-pulse-dot"></span>
                    <span>Loading premium customer profile...</span>
                </div>
            `;
        };

        viewButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                const url = button.dataset.detailsUrl;
                const actionConfig = {
                    workspaceUrl: String(button.dataset.workspaceUrl || ''),
                    templateBaseUrl: String(button.dataset.templateBaseUrl || ''),
                    draftUrl: String(button.dataset.draftUrl || ''),
                    sendUrl: String(button.dataset.sendUrl || ''),
                    signedUrl: String(button.dataset.signedUrl || ''),
                    customerName: String(button.dataset.customerName || ''),
                };
                openModal();
                setLoading();

                try {
                    const response = await fetch(url, {
                        method: 'GET',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });

                    const payload = await response.json();

                    if (!response.ok || !payload.success) {
                        renderError(payload.message || 'Unable to fetch customer details.');
                        return;
                    }

                    renderDetails(payload.data || {}, actionConfig);
                } catch (error) {
                    renderError('Network error while loading customer details.');
                }
            });
        });

        modal.querySelectorAll('[data-close-customer-modal]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();

    (() => {
        const modal = document.getElementById('customerAgreementModal');
        const body = document.getElementById('agreementModalBody');

        if (!modal || !body) {
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        let workspace = null;
        let currentConfig = null;
        let currentAgreementId = null;
        let currentDraftId = null;

        const esc = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const showLoading = (message = 'Preparing agreement workspace...') => {
            body.innerHTML = `
                <div class="agreement-modal__loading">
                    <span class="customer-pulse-dot"></span>
                    <span>${esc(message)}</span>
                </div>
            `;
        };

        const showError = (message) => {
            body.innerHTML = `
                <div class="agreement-modal__loading" style="flex-direction:column; text-align:center; min-height:240px;">
                    <div class="text-3xl">⚠</div>
                    <div>${esc(message || 'Unable to load agreement workspace.')}</div>
                </div>
            `;
        };

        const recordsForStatus = (status) => {
            if (!workspace || !Array.isArray(workspace.records)) {
                return [];
            }

            return workspace.records.filter((record) => String(record.status || '').toLowerCase() === status);
        };

        const getDraftById = (draftId) => {
            if (!workspace || !Array.isArray(workspace.records)) {
                return null;
            }

            return workspace.records.find((record) => String(record.id || '') === String(draftId)) || null;
        };

        const stripHtml = (value) => String(value || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();

        const looksLikeHtml = (value) => /<\/?[a-z][\s\S]*>/i.test(String(value || ''));

        const decodeHtmlEntities = (value) => {
            const parser = document.createElement('textarea');
            parser.innerHTML = String(value || '');
            return parser.value;
        };

        const normalizeEditorHtml = (value) => {
            const raw = String(value || '').trim();
            if (raw === '') {
                return '';
            }

            if (looksLikeHtml(raw)) {
                return raw;
            }

            const decoded = decodeHtmlEntities(raw);
            if (looksLikeHtml(decoded)) {
                return decoded;
            }

            return `<p>${esc(decoded)}</p>`;
        };

        const sanitizeEditorHtml = (value) => {
            const wrapper = document.createElement('div');
            wrapper.innerHTML = String(value || '');

            wrapper.querySelectorAll('script,style,iframe,object,embed,link,meta').forEach((node) => node.remove());
            wrapper.querySelectorAll('*').forEach((node) => {
                Array.from(node.attributes).forEach((attribute) => {
                    const name = attribute.name.toLowerCase();
                    const attrValue = String(attribute.value || '');

                    if (name.startsWith('on')) {
                        node.removeAttribute(attribute.name);
                        return;
                    }

                    if ((name === 'href' || name === 'src') && /^javascript:/i.test(attrValue.trim())) {
                        node.removeAttribute(attribute.name);
                    }
                });
            });

            return wrapper.innerHTML;
        };

        const setEditorHtml = (editor, html) => {
            if (!editor) {
                return;
            }

            const normalized = normalizeEditorHtml(html);
            editor.innerHTML = sanitizeEditorHtml(normalized);
        };

        const getEditorHtml = (editor) => {
            if (!editor) {
                return '';
            }

            const sanitized = sanitizeEditorHtml(editor.innerHTML || '');
            const compact = sanitized
                .replace(/<p><br><\/p>/gi, '')
                .replace(/<div><br><\/div>/gi, '')
                .trim();

            return compact;
        };

        const buildAgreementList = () => {
            if (!workspace || !Array.isArray(workspace.agreements) || workspace.agreements.length === 0) {
                return '<p class="text-sm opacity-80">No agreements available from API.</p>';
            }

            return workspace.agreements.map((agreement) => {
                const isActive = Number(agreement.id) === Number(currentAgreementId);
                const taggedBadge = agreement.is_tagged ? '<span class="text-[10px] px-2 py-0.5 rounded-full" style="background: rgba(110,231,183,0.2); color:#6ee7b7">Tagged</span>' : '';

                return `
                    <button type="button" class="agreement-item ${isActive ? 'active' : ''}" data-agreement-id="${Number(agreement.id)}">
                        <div class="flex items-center justify-between gap-2">
                            <strong class="text-sm">${esc(agreement.agreement_type || 'Agreement')}</strong>
                            ${taggedBadge}
                        </div>
                        <div class="mt-1 text-xs opacity-80">#${Number(agreement.id)} · Company ${Number(agreement.id_company || 0)}</div>
                    </button>
                `;
            }).join('');
        };

        const buildTimeline = () => {
            const drafts = recordsForStatus('draft');
            const sent = recordsForStatus('sent');

            const draftMarkup = drafts.length === 0
                ? '<div class="text-xs opacity-70">No draft saved yet.</div>'
                : drafts.map((row) => `
                    <label class="timeline-row block cursor-pointer">
                        <div class="flex items-center justify-between gap-2">
                            <div>
                                <div class="font-semibold text-sm">${esc(row.agreement_type || 'Agreement')}</div>
                                <div class="text-xs opacity-80">${esc(row.file_name || '')}</div>
                            </div>
                            <input type="checkbox" class="js-draft-select" value="${esc(row.id)}">
                        </div>
                    </label>
                `).join('');

            const sentMarkup = sent.length === 0
                ? '<div class="text-xs opacity-70">No sent agreement yet.</div>'
                : sent.map((row) => `
                    <div class="timeline-row">
                        <div class="font-semibold text-sm">${esc(row.agreement_type || 'Agreement')}</div>
                        <div class="text-xs opacity-80">Sent: ${esc(row.sent_at || '—')}</div>
                    </div>
                `).join('');

            return `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-4">
                    <div class="agreement-card">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <div class="font-semibold text-sm">Draft Queue</div>
                            <div class="flex items-center gap-2">
                                <button type="button" id="selectAllDrafts" class="text-xs font-semibold" style="color:#7dd3fc">Select all</button>
                                <button type="button" id="clearDraftSelection" class="text-xs font-semibold" style="color:#c4b5fd">Clear</button>
                            </div>
                        </div>
                        <div class="space-y-2">${draftMarkup}</div>
                    </div>
                    <div class="agreement-card">
                        <div class="font-semibold text-sm mb-2">Sent Timeline</div>
                        <div class="space-y-2">${sentMarkup}</div>
                    </div>
                </div>
            `;
        };

        const renderWorkspace = () => {
            const customerName = workspace?.customer?.company_name || currentConfig.customerName || 'Customer';
            const mergeFields = workspace?.merge_fields || {};
            const mergeChips = Object.keys(mergeFields).map((field) => {
                return `<button type="button" class="merge-chip" data-merge-token="$${esc(field)}">$${esc(field)}</button>`;
            }).join('');

            body.innerHTML = `
                <section class="agreement-hero">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="agreementModalTitle" class="text-xl font-black">Agreement Tagging Workspace</h2>
                            <p class="text-sm mt-1 opacity-90">Customer: ${esc(customerName)} · Build drafts, mail-merge fields, and send contracts.</p>
                        </div>
                        <div class="text-xs px-3 py-1 rounded-full" style="background: rgba(255,255,255,0.16)">${(workspace?.agreements || []).length} agreements available</div>
                    </div>
                </section>

                <section class="agreement-grid">
                    <aside class="agreement-card">
                        <h3 class="font-bold text-sm mb-2">Contract Agreement List</h3>
                        <div id="agreementList" class="space-y-2 max-h-[52vh] overflow-auto pr-1">${buildAgreementList()}</div>
                    </aside>

                    <div class="space-y-3">
                        <div class="agreement-card">
                            <div class="flex items-center justify-between gap-3 mb-3">
                                <h3 class="font-bold text-sm">Editor & Mail Merge</h3>
                                <span class="text-xs opacity-80">Use tokens like $company_name inside the contract</span>
                            </div>
                            <div class="flex flex-wrap gap-2 mb-3">${mergeChips || '<span class="text-xs opacity-70">No merge fields available.</span>'}</div>
                            <div class="agreement-editor-toolbar" id="agreementEditorToolbar">
                                <button type="button" class="agreement-editor-tool" data-editor-cmd="bold">Bold</button>
                                <button type="button" class="agreement-editor-tool" data-editor-cmd="italic">Italic</button>
                                <button type="button" class="agreement-editor-tool" data-editor-cmd="underline">Underline</button>
                                <button type="button" class="agreement-editor-tool" data-editor-cmd="insertUnorderedList">Bullet</button>
                                <button type="button" class="agreement-editor-tool" data-editor-cmd="insertOrderedList">Number</button>
                                <select class="agreement-editor-tool select" data-editor-cmd="formatBlock">
                                    <option value="P">Paragraph</option>
                                    <option value="H1">Heading 1</option>
                                    <option value="H2">Heading 2</option>
                                    <option value="H3">Heading 3</option>
                                </select>
                                <select class="agreement-editor-tool select" data-editor-cmd="fontName">
                                    <option value="Arial">Arial</option>
                                    <option value="Times New Roman">Times</option>
                                    <option value="Calibri">Calibri</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Verdana">Verdana</option>
                                </select>
                            </div>
                            <div id="agreementEditor" class="agreement-editor" contenteditable="true" data-placeholder="Select an agreement to load template content..."></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                                <input id="agreementRemarks" type="text" class="form-input" placeholder="Remarks (optional)">
                                <input id="agreementFinYear" type="text" class="form-input" value="${new Date().getFullYear()}" placeholder="Financial year code">
                            </div>
                            <div class="flex flex-wrap gap-2 mt-4">
                                <button type="button" id="saveAgreementDraft" class="btn btn-primary">Save Draft</button>
                                <button type="button" id="sendAgreementDraft" class="btn btn-success">Final: Send To Customer</button>
                            </div>
                            <p id="agreementStatusLine" class="text-xs mt-2 opacity-80"></p>
                        </div>

                        ${buildTimeline()}

                        <div class="agreement-card" id="batchReviewPanel">
                            <div class="font-semibold text-sm mb-2">Batch Review</div>
                            <div class="text-xs opacity-80">Select one or more drafts to preview and send.</div>
                        </div>
                    </div>
                </section>
            `;

            bindWorkspaceEvents();
        };

        const refreshBatchReviewPanel = () => {
            const panel = document.getElementById('batchReviewPanel');
            if (!panel) {
                return;
            }

            const selectedIds = Array.from(body.querySelectorAll('.js-draft-select:checked')).map((node) => String(node.value || ''));
            const selectedDrafts = selectedIds
                .map((id) => getDraftById(id))
                .filter((item) => item !== null);

            if (selectedDrafts.length === 0) {
                panel.innerHTML = `
                    <div class="font-semibold text-sm mb-2">Batch Review</div>
                    <div class="text-xs opacity-80">Select one or more drafts to preview and send.</div>
                `;
                return;
            }

            const cards = selectedDrafts.map((row) => {
                const previewText = stripHtml(row.content || '').slice(0, 220);

                return `
                    <div class="timeline-row">
                        <div class="flex items-center justify-between gap-2">
                            <div class="font-semibold text-sm">${esc(row.agreement_type || 'Agreement')}</div>
                            <button type="button" class="text-xs font-semibold js-load-draft" data-draft-id="${esc(row.id)}" style="color:#93c5fd">Load in editor</button>
                        </div>
                        <div class="text-xs opacity-80 mt-1">${esc(row.file_name || '')}</div>
                        <div class="text-xs opacity-90 mt-2" style="line-height:1.5">${esc(previewText)}${previewText.length >= 220 ? '...' : ''}</div>
                    </div>
                `;
            }).join('');

            panel.innerHTML = `
                <div class="flex items-center justify-between gap-2 mb-2">
                    <div class="font-semibold text-sm">Batch Review</div>
                    <div class="text-xs opacity-85">${selectedDrafts.length} draft(s) selected</div>
                </div>
                <div class="space-y-2">${cards}</div>
            `;

            panel.querySelectorAll('.js-load-draft').forEach((button) => {
                button.addEventListener('click', () => {
                    const draftId = String(button.dataset.draftId || '');
                    const draft = getDraftById(draftId);
                    if (!draft) {
                        return;
                    }

                    const editor = document.getElementById('agreementEditor');
                    if (!editor) {
                        return;
                    }

                    currentDraftId = draftId;
                    currentAgreementId = Number(draft.agreement_id || 0);
                    setEditorHtml(editor, String(draft.content || ''));
                    setStatusLine('Loaded selected draft into editor for final review.');
                });
            });
        };

        const insertToken = (editor, token) => {
            editor.focus();

            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) {
                editor.innerHTML += esc(token);
                return;
            }

            const range = selection.getRangeAt(0);
            if (!editor.contains(range.commonAncestorContainer)) {
                editor.innerHTML += esc(token);
                return;
            }

            range.deleteContents();
            const textNode = document.createTextNode(token);
            range.insertNode(textNode);
            range.setStartAfter(textNode);
            range.setEndAfter(textNode);
            selection.removeAllRanges();
            selection.addRange(range);
        };

        const setStatusLine = (text, isError = false) => {
            const node = document.getElementById('agreementStatusLine');
            if (!node) {
                return;
            }

            node.textContent = text;
            node.style.color = isError ? '#fca5a5' : '#93c5fd';
        };

        const loadTemplate = async (agreementId) => {
            if (!currentConfig) {
                return;
            }

            currentAgreementId = Number(agreementId);
            renderWorkspace();
            setStatusLine('Loading agreement template...');

            const editor = document.getElementById('agreementEditor');
            if (!editor) {
                return;
            }

            try {
                const response = await fetch(`${currentConfig.templateBaseUrl}/${agreementId}`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    setStatusLine(payload.message || 'Unable to load selected agreement template.', true);
                    return;
                }

                setEditorHtml(editor, String(payload?.data?.content || ''));
                setStatusLine('Template loaded. Edit and save draft when ready.');
            } catch (error) {
                setStatusLine('Network error while loading agreement template.', true);
            }
        };

        const postJson = async (url, data) => {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(data),
            });

            const payload = await response.json();
            return { response, payload };
        };

        const refreshWorkspace = async () => {
            if (!currentConfig) {
                return;
            }

            const response = await fetch(currentConfig.workspaceUrl, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Could not refresh agreement workspace.');
            }

            workspace = payload.data || {};
        };

        const bindWorkspaceEvents = () => {
            body.querySelectorAll('.agreement-item').forEach((button) => {
                button.addEventListener('click', () => {
                    const agreementId = Number(button.dataset.agreementId || 0);
                    if (agreementId > 0) {
                        loadTemplate(agreementId);
                    }
                });
            });

            const editor = document.getElementById('agreementEditor');
            const toolbar = document.getElementById('agreementEditorToolbar');
            body.querySelectorAll('[data-merge-token]').forEach((chip) => {
                chip.addEventListener('click', () => {
                    if (!editor) {
                        return;
                    }

                    insertToken(editor, String(chip.dataset.mergeToken || ''));
                });
            });

            if (toolbar && editor) {
                toolbar.querySelectorAll('[data-editor-cmd]').forEach((node) => {
                    const command = String(node.dataset.editorCmd || '');

                    if (node.tagName === 'SELECT') {
                        node.addEventListener('change', () => {
                            editor.focus();
                            document.execCommand(command, false, node.value);
                        });
                        return;
                    }

                    node.addEventListener('click', () => {
                        editor.focus();
                        document.execCommand(command, false, null);
                    });
                });
            }

            body.querySelectorAll('.js-draft-select').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    if (checkbox.checked) {
                        currentDraftId = String(checkbox.value || '');
                    } else if (currentDraftId === String(checkbox.value || '')) {
                        currentDraftId = null;
                    }

                    refreshBatchReviewPanel();
                });
            });

            const selectAllButton = document.getElementById('selectAllDrafts');
            if (selectAllButton) {
                selectAllButton.addEventListener('click', () => {
                    const boxes = Array.from(body.querySelectorAll('.js-draft-select'));
                    boxes.forEach((box) => {
                        box.checked = true;
                    });

                    if (boxes.length > 0) {
                        currentDraftId = String(boxes[0].value || '');
                    }

                    refreshBatchReviewPanel();
                });
            }

            const clearSelectionButton = document.getElementById('clearDraftSelection');
            if (clearSelectionButton) {
                clearSelectionButton.addEventListener('click', () => {
                    body.querySelectorAll('.js-draft-select').forEach((box) => {
                        box.checked = false;
                    });
                    currentDraftId = null;
                    refreshBatchReviewPanel();
                });
            }

            const saveButton = document.getElementById('saveAgreementDraft');
            if (saveButton) {
                saveButton.addEventListener('click', async () => {
                    if (!currentConfig) {
                        return;
                    }

                    if (!currentAgreementId) {
                        setStatusLine('Please select an agreement from the list first.', true);
                        return;
                    }

                    const remarks = document.getElementById('agreementRemarks')?.value || '';
                    const finYear = document.getElementById('agreementFinYear')?.value || '';
                    const content = getEditorHtml(document.getElementById('agreementEditor'));

                    saveButton.disabled = true;
                    setStatusLine('Saving draft...');

                    try {
                        const { response, payload } = await postJson(currentConfig.draftUrl, {
                            agreement_id: currentAgreementId,
                            edited_content: content,
                            remarks: remarks,
                            fin_year_code: finYear,
                        });

                        if (!response.ok || !payload.success) {
                            setStatusLine(payload.message || 'Draft save failed.', true);
                            return;
                        }

                        currentDraftId = String(payload?.data?.record?.id || '');
                        await refreshWorkspace();
                        renderWorkspace();
                        const nextEditor = document.getElementById('agreementEditor');
                        if (nextEditor) {
                            setEditorHtml(nextEditor, content);
                        }
                        setStatusLine('Draft saved. You can now send it to customer.');
                    } catch (error) {
                        setStatusLine('Network error while saving draft.', true);
                    } finally {
                        saveButton.disabled = false;
                    }
                });
            }

            const sendButton = document.getElementById('sendAgreementDraft');
            if (sendButton) {
                sendButton.addEventListener('click', async () => {
                    if (!currentConfig) {
                        return;
                    }

                    const selected = Array.from(body.querySelectorAll('.js-draft-select:checked')).map((node) => String(node.value || ''));
                    const draftIds = selected.length > 0 ? selected : (currentDraftId ? [currentDraftId] : []);

                    if (draftIds.length === 0) {
                        setStatusLine('Select or save at least one draft before sending.', true);
                        return;
                    }

                    const remarks = document.getElementById('agreementRemarks')?.value || '';
                    const finYear = document.getElementById('agreementFinYear')?.value || '';

                    sendButton.disabled = true;
                    setStatusLine('Sending agreement to customer...');

                    try {
                        const { response, payload } = await postJson(currentConfig.sendUrl, {
                            draft_ids: draftIds,
                            remarks: remarks,
                            fin_year_code: finYear,
                        });

                        if (!response.ok || !payload.success) {
                            setStatusLine(payload.message || 'Send failed.', true);
                            return;
                        }

                        await refreshWorkspace();
                        renderWorkspace();
                        setStatusLine(payload.message || 'Agreement sent to customer successfully.');
                    } catch (error) {
                        setStatusLine('Network error while sending agreement.', true);
                    } finally {
                        sendButton.disabled = false;
                    }
                });
            }

            refreshBatchReviewPanel();
        };

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('.js-tag-agreement');
            if (!button) {
                return;
            }

            currentConfig = {
                workspaceUrl: String(button.dataset.workspaceUrl || ''),
                templateBaseUrl: String(button.dataset.templateBaseUrl || ''),
                draftUrl: String(button.dataset.draftUrl || ''),
                sendUrl: String(button.dataset.sendUrl || ''),
                customerName: String(button.dataset.customerName || ''),
            };

            currentAgreementId = null;
            currentDraftId = null;
            openModal();
            showLoading();

            try {
                await refreshWorkspace();
                renderWorkspace();
            } catch (error) {
                showError(error.message || 'Failed to initialize agreement workspace.');
            }
        });

        modal.querySelectorAll('[data-close-agreement-modal]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();

    (() => {
        const modal = document.getElementById('customerSignedModal');
        const body = document.getElementById('signedModalBody');

        if (!modal || !body) {
            return;
        }

        const esc = (value) => String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');

        const openModal = () => {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';
        };

        const closeModal = () => {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
        };

        const showLoading = () => {
            body.innerHTML = `
                <div class="agreement-modal__loading">
                    <span class="customer-pulse-dot"></span>
                    <span>Loading signed agreement copies...</span>
                </div>
            `;
        };

        const showError = (message) => {
            body.innerHTML = `
                <div class="agreement-modal__loading" style="flex-direction:column; text-align:center; min-height:240px;">
                    <div class="text-3xl">⚠</div>
                    <div>${esc(message || 'Could not load signed copies.')}</div>
                </div>
            `;
        };

        const formatDate = (value) => {
            if (!value) {
                return '—';
            }

            const date = new Date(value);
            if (Number.isNaN(date.getTime())) {
                return String(value);
            }

            return date.toLocaleString();
        };

        const renderSignedModal = (customerName, records) => {
            const safeCustomerName = esc(customerName || 'Customer');

            if (!Array.isArray(records) || records.length === 0) {
                body.innerHTML = `
                    <section class="agreement-hero">
                        <h2 id="signedModalTitle" class="text-xl font-black">Signed Agreement Copies</h2>
                        <p class="text-sm mt-1 opacity-90">Customer: ${safeCustomerName}</p>
                    </section>
                    <div class="signed-card text-center py-10">
                        <div class="text-4xl mb-2">📭</div>
                        <div class="font-semibold">No signed copies yet</div>
                        <div class="text-xs mt-1 opacity-80">Signed contracts will appear here after customer acknowledgement.</div>
                    </div>
                `;
                return;
            }

            const listMarkup = records.map((record, index) => {
                return `
                    <button type="button" class="signed-item ${index === 0 ? 'active' : ''}" data-signed-index="${index}">
                        <div class="font-semibold text-sm">${esc(record.agreement_type || 'Agreement')}</div>
                        <div class="text-xs opacity-80 mt-1">Signed by ${esc(record.signer_name || 'Customer')}</div>
                        <div class="text-xs opacity-80">${esc(formatDate(record.signed_at))}</div>
                    </button>
                `;
            }).join('');

            body.innerHTML = `
                <section class="agreement-hero">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h2 id="signedModalTitle" class="text-xl font-black">Signed Agreement Copies</h2>
                            <p class="text-sm mt-1 opacity-90">Customer: ${safeCustomerName}</p>
                        </div>
                        <div class="text-xs px-3 py-1 rounded-full" style="background: rgba(255,255,255,0.16)">${records.length} signed copy(s)</div>
                    </div>
                </section>
                <section class="signed-shell">
                    <aside class="signed-card">
                        <div class="font-semibold text-sm mb-2">Signed List</div>
                        <div class="space-y-2" id="signedList">${listMarkup}</div>
                    </aside>
                    <div class="signed-card" id="signedPreviewPanel"></div>
                </section>
            `;

            const renderPreview = (index) => {
                const record = records[index];
                if (!record) {
                    return;
                }

                const signatureMarkup = record.signature_preview
                    ? `<img class="signed-signature" alt="Signature" src="${esc(record.signature_preview)}">`
                    : '<span class="text-xs opacity-80">No signature preview available.</span>';

                const downloadButton = record.download_ready
                    ? `<a href="${esc(record.download_url)}" class="btn btn-primary">Download Signed DOCX</a>`
                    : '<button type="button" class="btn btn-outline" disabled>Signed File Not Found</button>';

                const panel = document.getElementById('signedPreviewPanel');
                if (!panel) {
                    return;
                }

                panel.innerHTML = `
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                        <h3 class="font-bold text-lg">${esc(record.agreement_type || 'Agreement')}</h3>
                        <span class="badge badge-success">${esc(record.status || 'acknowledged')}</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-4 text-sm">
                        <div><span class="opacity-80">Signer:</span> <strong>${esc(record.signer_name || 'Customer')}</strong></div>
                        <div><span class="opacity-80">Signed At:</span> <strong>${esc(formatDate(record.signed_at))}</strong></div>
                        <div><span class="opacity-80">Financial Year:</span> <strong>${esc(record.fin_year_code || '—')}</strong></div>
                        <div><span class="opacity-80">Remarks:</span> <strong>${esc(record.remarks || '—')}</strong></div>
                    </div>
                    <div class="mb-4">
                        <div class="text-xs mb-2 opacity-80">Customer Signature</div>
                        ${signatureMarkup}
                    </div>
                    <div class="mb-4">
                        <div class="text-xs mb-2 opacity-80">Signed Contract Content</div>
                        <div class="signed-content-preview contract-prose">${record.content || '<p>No preview available.</p>'}</div>
                    </div>
                    <div class="flex justify-end">${downloadButton}</div>
                `;
            };

            body.querySelectorAll('.signed-item').forEach((item) => {
                item.addEventListener('click', () => {
                    const index = Number(item.dataset.signedIndex || 0);
                    body.querySelectorAll('.signed-item').forEach((entry) => entry.classList.remove('active'));
                    item.classList.add('active');
                    renderPreview(index);
                });
            });

            renderPreview(0);
        };

        document.addEventListener('click', async (event) => {
            const button = event.target.closest('.js-view-signed');
            if (!button) {
                return;
            }

            const signedUrl = String(button.dataset.signedUrl || '');
            const customerName = String(button.dataset.customerName || 'Customer');

            openModal();
            showLoading();

            try {
                const response = await fetch(signedUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    showError(payload.message || 'Unable to load signed copies.');
                    return;
                }

                renderSignedModal(customerName, payload?.data?.records || []);
            } catch (error) {
                showError('Network error while loading signed copies.');
            }
        });

        modal.querySelectorAll('[data-close-signed-modal]').forEach((el) => {
            el.addEventListener('click', closeModal);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                closeModal();
            }
        });
    })();
</script>
@endpush
