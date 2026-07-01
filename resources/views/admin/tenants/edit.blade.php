@extends('layouts.app')
@section('title', 'Edit ' . $tenant->name)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-2xl">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Edit Customer</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $tenant->name }}</p>
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

        <form method="POST" action="{{ route('admin.tenants.update', $tenant) }}" class="space-y-6">
            @csrf @method('PUT')
            <div>
                <label class="form-label">Company Name <span class="text-red-400">*</span></label>
                <input type="text" name="name" value="{{ old('name', $tenant->name) }}" required class="form-input">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Business Email <span class="text-red-400">*</span></label>
                    <input type="email" name="contact_email" value="{{ old('email', $tenant->email) }}" required class="form-input">
                </div>
                <div>
                    <label class="form-label">Phone</label>
                    <input type="text" name="contact_phone" value="{{ old('phone', $tenant->phone) }}" class="form-input">
                </div>
            </div>
            <div>
                <label class="form-label">Website</label>
                <input type="url" name="website" value="{{ old('website', $tenant->website) }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Industry</label>
                <select name="industry" class="form-input">
                    <option value="">Select industry</option>
                    @foreach(['Technology', 'Healthcare', 'Finance', 'Retail', 'Manufacturing', 'Education', 'Real Estate', 'Media', 'Other'] as $ind)
                    <option value="{{ $ind }}" {{ old('industry', $tenant->industry) === $ind ? 'selected' : '' }}>{{ $ind }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Address</label>
                <textarea name="address" rows="2" class="form-input">{{ old('address', $tenant->address) }}</textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Save Changes</button>
                <a href="{{ route('admin.tenants.show', $tenant) }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
