@extends('layouts.app')
@section('title', 'New Support Ticket')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp max-w-2xl">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.tickets') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Open a Support Ticket</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Describe your issue and we'll get back to you soon</p>
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

        <form method="POST" action="{{ route('customer.tickets.store') }}" class="space-y-6">
            @csrf
            <div>
                <label class="form-label">Subject <span class="text-red-400">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required
                       class="form-input" placeholder="Brief description of your issue">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Category</label>
                    <select name="category" class="form-input">
                        <option value="general" {{ old('category') === 'general' ? 'selected' : '' }}>General</option>
                        <option value="billing" {{ old('category') === 'billing' ? 'selected' : '' }}>Billing</option>
                        <option value="technical" {{ old('category') === 'technical' ? 'selected' : '' }}>Technical</option>
                        <option value="contract" {{ old('category') === 'contract' ? 'selected' : '' }}>Contract</option>
                    </select>
                </div>
                <div>
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-input">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>Medium</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="form-label">Message <span class="text-red-400">*</span></label>
                <textarea name="message" rows="6" required class="form-input"
                          placeholder="Please describe your issue in detail...">{{ old('message') }}</textarea>
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-primary">Submit Ticket</button>
                <a href="{{ route('customer.tickets') }}" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
