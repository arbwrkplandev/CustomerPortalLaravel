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

        <form method="POST" action="{{ route('admin.tenants.store') }}" class="space-y-6">
            @csrf
            <div>
                <label class="form-label">Company Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name') }}" required class="form-input" placeholder="Acme Corp">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Business Email <span class="text-red-400">*</span></label>
                    <input type="email" name="contact_email" value="{{ old('email') }}" required class="form-input" placeholder="contact@company.com">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('phone') }}" class="form-input" placeholder="+1 555 000 0000">
                </div>
            </div>
            <div>
                <label class="form-label">Website</label>
                <input type="url" name="website" value="{{ old('website') }}" class="form-input" placeholder="https://company.com">
            </div>
            <div>
                <label class="form-label">Industry</label>
                <select name="industry" class="form-input">
                    <option value="">Select industry</option>
                    @foreach(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing', 'Education', 'Real Estate', 'Media', 'Other'] as $ind)
                    <option value="{{ $ind }}" {{ old('industry') === $ind ? 'selected' : '' }}>{{ $ind }}</option>
                    @endforeach
                </select>
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
                    <label class="form-label">Contact Email <span class="text-red-400">*</span></label>
                    <input type="email" name="contact_email" value="{{ old('contact_email') }}" required class="form-input" placeholder="john@company.com">
                </div>
            </div>
            <div>
                <label class="form-label">Password <span class="text-red-400">*</span></label>
                <input type="password" name="contact_password" required class="form-input" placeholder="Min 8 characters">
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Create Customer</button>
                <a href="{{ route('admin.tenants.index') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
