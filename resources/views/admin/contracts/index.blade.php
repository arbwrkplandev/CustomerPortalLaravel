@extends('layouts.app')
@section('title', 'Contracts')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Contracts</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Manage customer agreements and e-sign workflow</p>
        </div>
        <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Contract
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1 min-w-48" placeholder="Search by title or number…">
            <select name="status" class="form-input w-44">
                <option value="">All Status</option>
                <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="pending_signature" {{ request('status') === 'pending_signature' ? 'selected' : '' }}>Awaiting Signature</option>
                <option value="signed" {{ request('status') === 'signed' ? 'selected' : '' }}>Signed</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if(request()->anyFilled(['search', 'status']))
            <a href="{{ route('admin.contracts.index') }}" class="btn btn-outline">Clear</a>
            @endif
        </form>
    </div>

    @if($contracts->isEmpty())
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">No contracts found</h3>
            <a href="{{ route('admin.contracts.create') }}" class="btn btn-primary mt-4">Create First Contract</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contract</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Sent</th>
                        <th>Signed</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $contract)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $contract->title }}</div>
                            <div class="text-xs font-mono mt-0.5" style="color: var(--color-text-secondary)">{{ $contract->contract_number }}</div>
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $contract->tenant->company_name }}</td>
                        <td>
                            @if($contract->status === 'signed')
                                <span class="badge badge-success">✓ Signed</span>
                            @elseif(in_array($contract->status, ['sent', 'pending_signature']))
                                <span class="badge badge-warning">⏳ Awaiting Signature</span>
                            @elseif($contract->status === 'draft')
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Draft</span>
                            @elseif($contract->status === 'expired')
                                <span class="badge badge-danger">Expired</span>
                            @else
                                <span class="badge">{{ ucfirst($contract->status) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $contract->sent_at ? $contract->sent_at->format('M d, Y') : '—' }}
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $contract->signed_at ? $contract->signed_at->format('M d, Y') : '—' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.contracts.show', $contract) }}" class="btn btn-outline btn-sm">View</a>
                                @if($contract->status === 'draft')
                                <form method="POST" action="{{ route('admin.contracts.send', $contract) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="btn btn-primary btn-sm" onclick="return confirm('Send to customer?')">Send</button>
                                </form>
                                @endif
                                @if($contract->signed_pdf_path)
                                <a href="{{ route('admin.contracts.download', [$contract, 'signed']) }}" class="btn btn-success btn-sm">⬇️</a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $contracts->links() }}</div>
    @endif
</div>
@endsection
