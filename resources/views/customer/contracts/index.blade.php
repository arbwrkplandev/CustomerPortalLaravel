@extends('layouts.app')
@section('title', 'My Contracts')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">My Contracts</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">View and e-sign your agreements</p>
        </div>
    </div>

    @if($contracts->isEmpty())
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <h3 class="text-lg font-semibold mb-2" style="color: var(--color-text-primary)">No contracts yet</h3>
            <p style="color: var(--color-text-secondary)">Your contracts will appear here when issued.</p>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Contract Title</th>
                        <th>Issued</th>
                        <th>Status</th>
                        <th>Signed On</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $contract)
                    <tr>
                        <td>
                            <div class="font-semibold" style="color: var(--color-text-primary)">{{ $contract->title }}</div>
                            @if($contract->description)
                                <div class="text-sm mt-0.5" style="color: var(--color-text-secondary)">{{ Str::limit($contract->description, 60) }}</div>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $contract->created_at->format('M d, Y') }}</td>
                        <td>
                            @if($contract->status === 'signed')
                                <span class="badge badge-success">Signed</span>
                            @elseif(in_array($contract->status, ['sent', 'pending_signature']))
                                <span class="badge badge-warning">Awaiting Signature</span>
                            @elseif($contract->status === 'draft')
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">Draft</span>
                            @else
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">{{ ucfirst(str_replace('_', ' ', $contract->status)) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $contract->signed_at ? $contract->signed_at->format('M d, Y') : '—' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.contracts.show', $contract) }}" class="btn btn-outline btn-sm">
                                    {{ in_array($contract->status, ['sent','pending_signature']) ? 'Review & Sign' : 'View' }}
                                </a>
                                @if($contract->signed_pdf_path)
                                <a href="{{ route('customer.contracts.download', [$contract, 'signed']) }}" class="btn btn-outline btn-sm" title="Download Signed Copy">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
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
