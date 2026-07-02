@php
    $plan = $plan ?? null;
@endphp

<div>
    <label class="form-label">Plan Name <span class="text-red-400">*</span></label>
    <input type="text" name="name" value="{{ old('name', $plan?->name) }}" required class="form-input" placeholder="Professional">
</div>

<div>
    <label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-input" placeholder="Plan benefits and ideal customer profile">{{ old('description', $plan?->description) }}</textarea>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="form-label">Monthly Rate <span class="text-red-400">*</span></label>
        <input type="number" step="0.01" min="0" name="monthly_price" value="{{ old('monthly_price', $plan?->monthly_price ?? 0) }}" required class="form-input" placeholder="49.99">
    </div>
    <div>
        <label class="form-label">Quarterly Rate <span class="text-red-400">*</span></label>
        <input type="number" step="0.01" min="0" name="quarterly_price" value="{{ old('quarterly_price', $plan?->quarterly_price ?? 0) }}" required class="form-input" placeholder="139.99">
    </div>
    <div>
        <label class="form-label">Annual Rate <span class="text-red-400">*</span></label>
        <input type="number" step="0.01" min="0" name="annual_price" value="{{ old('annual_price', $plan?->annual_price ?? 0) }}" required class="form-input" placeholder="499.99">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="form-label">Max Users <span class="text-red-400">*</span></label>
        <input type="number" min="1" name="max_users" value="{{ old('max_users', $plan?->max_users ?? 1) }}" required class="form-input" placeholder="25">
    </div>
    <div>
        <label class="form-label">Sort Order</label>
        <input type="number" min="0" name="sort_order" value="{{ old('sort_order', $plan?->sort_order ?? 0) }}" class="form-input" placeholder="10">
    </div>
</div>

<div>
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $plan?->is_active ?? true))>
        <span class="text-sm" style="color: var(--color-text-secondary)">Plan is active and available for assignment</span>
    </label>
</div>
