@extends('layouts.app')
@section('title', 'My Invoices')
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Invoices</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Your billing history</p>
        </div>
    </div>

    @if($invoices->isEmpty())
        <div class="card text-center py-16">
            <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
            </svg>
            <h3 class="text-lg font-semibold" style="color: var(--color-text-primary)">No invoices yet</h3>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Issued</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td>
                            <span class="font-mono font-semibold" style="color: var(--color-brand-primary)">{{ $invoice->invoice_number }}</span>
                        </td>
                        <td>
                            <span class="font-bold" style="color: var(--color-text-primary)">${{ number_format($invoice->total_amount, 2) }}</span>
                        </td>
                        <td>
                            @if($invoice->status === 'paid')
                                <span class="badge badge-success">Paid</span>
                            @elseif($invoice->status === 'overdue')
                                <span class="badge badge-danger">Overdue</span>
                            @elseif($invoice->status === 'pending')
                                <span class="badge badge-warning">Pending</span>
                            @else
                                <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">{{ ucfirst($invoice->status) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : '—' }}
                        </td>
                        <td style="color: var(--color-text-secondary)">{{ $invoice->created_at->format('M d, Y') }}</td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('customer.invoices.show', $invoice) }}" class="btn btn-outline btn-sm">View</a>
                                <a href="{{ route('customer.invoices.download', $invoice) }}" class="btn btn-outline btn-sm">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    @endif
</div>
@endsection
