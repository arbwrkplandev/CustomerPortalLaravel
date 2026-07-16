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
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search customers..." class="form-input flex-1">
        <select name="status" class="form-input w-36">
            <option value="">All Status</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="trial" @selected(request('status') === 'trial')>Trial</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </select>
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
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
            <tbody>
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
@endsection

@push('styles')
<style>
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
</style>
@endpush

@push('scripts')
<script>
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

        const renderDetails = (payload) => {
            const customer = payload.customer || {};
            const contacts = Array.isArray(customer.contacts) ? customer.contacts : [];
            const createdAt = toText(customer.created_at, 'Unknown date').replaceAll('/', '-');

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
                                ${escapeHtml(customerInitials(customer.company_name))}
                            </div>
                            <div>
                                <h2 id="customerDetailsTitle" class="text-xl font-black">${escapeHtml(toText(customer.company_name, 'Customer'))}</h2>
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

                    renderDetails(payload.data || {});
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
</script>
@endpush
