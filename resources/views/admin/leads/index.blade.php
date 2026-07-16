@extends('layouts.app')
@section('portal-name', 'Admin Hub')
@section('page-title', 'Leads')
@section('page-subtitle', 'Manage incoming prospect leads')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-6">
    <form method="GET" class="flex gap-2 flex-1 max-w-lg">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search leads..." class="form-input flex-1">
        <button type="submit" class="btn btn-primary">Filter</button>
    </form>
</div>

<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Location</th>
                    <th>Source</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr class="animate-fadeInUp">
                    <td>
                        <div class="flex items-center gap-3">
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-sm font-bold flex-shrink-0"
                                 style="background: linear-gradient(135deg, #0ea5e9, #2563eb)">
                                {{ Str::upper(collect(explode(' ', $lead->company_name))->map(fn($word) => $word[0] ?? '')->implode('')) }}
                            </div>
                            <div>
                                <div class="font-semibold" style="color: var(--color-text)">{{ $lead->company_name ?: '—' }}</div>
                                <div class="text-xs" style="color: var(--color-text-muted)">Lead Code: {{ $lead->lead_code ?: '—' }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="text-sm font-medium" style="color: var(--color-text)">{{ $lead->contact_name ?: '—' }}</div>
                        <div class="text-xs" style="color: var(--color-text-muted)">{{ $lead->contact_email ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm" style="color: var(--color-text)">{{ implode(', ', array_filter([$lead->city, $lead->state, $lead->country])) ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="text-sm" style="color: var(--color-text)">{{ $lead->source_name ?: '—' }}</div>
                    </td>
                    <td class="text-sm" style="color: var(--color-text-muted)">
                        {{ $lead->created_at?->format('M d, Y') ?: '—' }}
                    </td>
                    <td>
                        <a href="{{ route('admin.leads.show', $lead->id) }}" class="btn btn-primary py-1.5 px-3 text-xs">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-12" style="color: var(--color-text-muted)">
                        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5V4H2v16h5m10 0v-4a3 3 0 00-3-3H10a3 3 0 00-3 3v4m10 0H7m5-12a3 3 0 110 6 3 3 0 010-6z"/></svg>
                        No leads found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4 flex items-center justify-between px-2">
        @if($leads->total() > 0)
            <p class="text-sm" style="color: var(--color-text-muted)">
                Showing {{ $leads->firstItem() }}-{{ $leads->lastItem() }} of {{ $leads->total() }} leads
            </p>
        @else
            <p class="text-sm" style="color: var(--color-text-muted)">No leads available</p>
        @endif
        {{ $leads->links() }}
    </div>
</div>
@endsection
