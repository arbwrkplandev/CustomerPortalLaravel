@extends('layouts.app')
@section('title', 'New Invoice')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-2xl">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">New Invoice</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Create a billing invoice</p>
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

        <form method="POST" action="{{ route('admin.invoices.store') }}" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Customer <span class="text-red-400">*</span></label>
                    <select name="tenant_id" class="form-input" required>
                        <option value="">Select customer</option>
                        @foreach($tenants as $tenant)
                        <option value="{{ $tenant->id }}" {{ old('tenant_id') == $tenant->id ? 'selected' : '' }}>{{ $tenant->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Description</label>
                <input type="text" name="description" value="{{ old('description') }}" class="form-input" placeholder="Monthly subscription — December 2025">
            </div>
            <div>
                <label class="form-label">Total Amount ($) <span class="text-red-400">*</span></label>
                <input type="number" name="total_amount" value="{{ old('total_amount') }}" required step="0.01" min="0" class="form-input" placeholder="0.00">
            </div>
            <div>
                <label class="form-label">Tax Amount ($)</label>
                <input type="number" name="tax_amount" value="{{ old('tax_amount', '0') }}" step="0.01" min="0" class="form-input">
            </div>
            <div>
                <label class="form-label">Notes</label>
                <textarea name="notes" rows="3" class="form-input" placeholder="Payment terms, bank details, etc.">{{ old('notes') }}</textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Create Invoice</button>
                <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
