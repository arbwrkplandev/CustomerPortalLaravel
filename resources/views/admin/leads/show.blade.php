@extends('layouts.app')
@section('title', $lead->company_name ?: 'Lead Details')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.leads.index') }}" class="btn btn-outline">← Leads</a>
        <div class="flex-1">
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">{{ $lead->company_name ?: 'Unnamed Lead' }}</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $lead->contact_email ?: 'No email provided' }}</p>
        </div>
        <span class="badge" style="background: rgba(14,165,233,0.12); color:#0284c7">
            Lead #{{ $lead->lead_code ?: $lead->id }}
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Company Details</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Company</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->company_name ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Lead Code</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->lead_code ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Company Size</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->company_size ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Location</span>
                        <span style="color:var(--color-text-primary)">{{ implode(', ', array_filter([$lead->city, $lead->state, $lead->country])) ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">ZIP</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->zip_code ?: '—' }}</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Tracking</h3>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Source</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->source_name ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Source ID</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->source_id ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Milestone ID</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->milestone_id ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span style="color:var(--color-text-secondary)">Product ID</span>
                        <span style="color:var(--color-text-primary)">{{ $lead->product_id ?? '—' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Primary Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Name</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_name ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Title</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_title ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Email</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_email ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Phone</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_phone ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Fax</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_fax ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Contact Created</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->contact_created_at?->format('M d, Y') ?: '—' }}</p>
                    </div>
                </div>
                <div class="mt-4 p-4 rounded-xl" style="background: var(--color-surface-2)">
                    <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Description</p>
                    <p class="text-sm whitespace-pre-line" style="color:var(--color-text-primary)">{{ $lead->contact_description ?: 'No notes available.' }}</p>
                </div>
            </div>

            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Address & Audit</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Address Line 1</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->address_line1 ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Address Line 2</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->address_line2 ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Created At</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->created_at?->format('M d, Y') ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Updated At</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->updated_at?->format('M d, Y') ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Created By</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->created_by ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide mb-1" style="color:var(--color-text-secondary)">Updated By</p>
                        <p style="color:var(--color-text-primary)">{{ $lead->updated_by ?: '—' }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
