@extends('layouts.app')
@section('title', 'Add Customer')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-2xl">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Add New Customer</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Create a new tenant account</p>
        </div>
    </div>

    <div class="card">
        @if($errors->any())
        <div class="mb-6 p-4 rounded-xl" style="background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3)">
            <ul class="list-disc list-inside space-y-1">
                @foreach($errors->all() as $error)
                <li class="text-sm text-red-400">{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form id="create-customer-form" method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-6">
            @csrf
            <div>
                <label class="form-label">Company Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Acme Corp">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Corp ID <span class="text-red-400">*</span></label>
                    <input type="text" name="corp_id" value="{{ old('corp_id') }}" required class="form-input" placeholder="ACME-IND">
                </div>
                <div>
                    <label class="form-label">Country</label>
                    <input type="text" name="country" value="{{ old('country') }}" class="form-input" placeholder="India">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Company Contact Email <span class="text-red-400">*</span></label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" required class="form-input" placeholder="contact@company.com">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('contact_phone') }}" class="form-input" placeholder="+1 555 000 0000">
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">City</label>
                    <input type="text" name="city" value="{{ old('city') }}" class="form-input" placeholder="Bengaluru">
                </div>
                <div>
                    <label class="form-label">Timezone</label>
                    <input type="text" name="timezone" value="{{ old('timezone') }}" class="form-input" placeholder="Asia/Kolkata">
                </div>
            </div>
            <div>
                <label class="form-label">Address</label>
                <textarea name="address" rows="2" class="form-input" placeholder="Street, City, State, Country">{{ old('address') }}</textarea>
            </div>
            <hr style="border-color: var(--color-border)">
            <h3 class="font-bold" style="color: var(--color-text-primary)">Primary Contact (Admin User)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Full Name <span class="text-red-400">*</span></label>
                    <input type="text" name="contact_name" value="{{ old('contact_name') }}" required class="form-input" placeholder="John Smith">
                </div>
                <div>
                    <label class="form-label">Login Email <span class="text-red-400">*</span></label>
                    <input type="email" name="user_email" value="{{ old('user_email') }}" required class="form-input" placeholder="john@company.com">
                </div>
            </div>
            <div>
                <label class="form-label">Username <span class="text-red-400">*</span></label>
                <input type="text" name="username" value="{{ old('username') }}" required class="form-input" placeholder="john.smith">
            </div>
            <div>
                <label class="form-label">Password <span class="text-red-400">*</span></label>
                <input type="password" name="contact_password" required class="form-input" placeholder="Min 8 characters">
            </div>
            <hr style="border-color: var(--color-border)">
            <h3 class="font-bold" style="color: var(--color-text-primary)">Initial Subscription (Optional)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Plan</label>
                    <select name="plan_id" class="form-input">
                        <option value="">No plan on creation</option>
                        @foreach($plans as $plan)
                        <option
                            value="{{ $plan->id }}"
                            data-monthly="{{ $plan->monthly_price }}"
                            data-quarterly="{{ $plan->quarterly_price }}"
                            data-annual="{{ $plan->annual_price }}"
                            {{ old('plan_id') == $plan->id ? 'selected' : '' }}>
                            {{ $plan->name }} (M: ${{ number_format($plan->monthly_price, 2) }}, Q: ${{ number_format($plan->quarterly_price, 2) }}, Y: ${{ number_format($plan->annual_price, 2) }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Billing Cycle</label>
                    <select name="billing_cycle" class="form-input">
                        <option value="monthly" {{ old('billing_cycle') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ old('billing_cycle') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                        <option value="annual" {{ old('billing_cycle') === 'annual' ? 'selected' : '' }}>Annual</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Plan Amount (Auto)</label>
                <input type="text" name="plan_amount_preview" class="form-input" readonly placeholder="Select plan and billing cycle">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Custom Rate (Optional)</label>
                    <input type="number" step="0.01" min="0" name="custom_rate" value="{{ old('custom_rate') }}" class="form-input" placeholder="Leave blank to use plan default">
                </div>
                <div>
                    <label class="form-label">Currency</label>
                    <input type="text" name="currency" value="{{ old('currency', 'USD') }}" class="form-input" maxlength="3" placeholder="USD">
                </div>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('create-customer-form');
    if (!form) return;

    const planSelect = form.querySelector('select[name="plan_id"]');
    const cycleSelect = form.querySelector('select[name="billing_cycle"]');
    const customRate = form.querySelector('input[name="custom_rate"]');
    const preview = form.querySelector('input[name="plan_amount_preview"]');

    const calculatePlanAmount = () => {
        if (!planSelect || !cycleSelect) return null;
        const selected = planSelect.options[planSelect.selectedIndex];
        if (!selected || !selected.value) return null;

        const cycle = cycleSelect.value || 'monthly';
        const raw = selected.dataset[cycle] || '0';
        const value = Number.parseFloat(raw);
        return Number.isNaN(value) ? 0 : value;
    };

    const refresh = () => {
        const amount = calculatePlanAmount();
        if (amount === null) {
            preview.value = '';
            return;
        }

        preview.value = amount.toFixed(2);
        if (!customRate.value) {
            customRate.value = amount.toFixed(2);
        }
    };

    planSelect.addEventListener('change', refresh);
    cycleSelect.addEventListener('change', refresh);
    refresh();
});
</script>
@endpush
