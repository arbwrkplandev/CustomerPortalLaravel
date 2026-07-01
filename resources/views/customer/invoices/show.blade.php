@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('portal-name', 'Customer Portal')

@section('sidebar-nav')
    @include('customer._sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('customer.invoices') }}" class="btn btn-outline">← Back</a>
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Invoice {{ $invoice->invoice_number }}</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $invoice->created_at->format('F d, Y') }}</p>
        </div>
        <div class="ml-auto">
            <a href="{{ route('customer.invoices.download', $invoice) }}" class="btn btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Download PDF
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <!-- Line Items -->
            <div class="card">
                <h2 class="text-lg font-bold mb-4" style="color: var(--color-text-primary)">Invoice Details</h2>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($invoice->line_items)
                            @foreach($invoice->line_items as $item)
                            <tr>
                                <td>{{ $item['description'] ?? 'Service' }}</td>
                                <td class="text-right">{{ $item['quantity'] ?? 1 }}</td>
                                <td class="text-right">${{ number_format($item['unit_price'] ?? 0, 2) }}</td>
                                <td class="text-right font-semibold">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
                <div class="mt-4 pt-4 border-t" style="border-color: var(--color-border)">
                    <div class="flex justify-between text-sm mb-1">
                        <span style="color: var(--color-text-secondary)">Subtotal</span>
                        <span>${{ number_format($invoice->subtotal ?? $invoice->total_amount, 2) }}</span>
                    </div>
                    @if($invoice->tax_amount)
                    <div class="flex justify-between text-sm mb-1">
                        <span style="color: var(--color-text-secondary)">Tax</span>
                        <span>${{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-lg mt-2">
                        <span style="color: var(--color-text-primary)">Total</span>
                        <span style="color: var(--color-brand-primary)">${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            <!-- Status -->
            <div class="card">
                <h3 class="font-bold mb-3" style="color: var(--color-text-primary)">Payment Status</h3>
                <div class="text-center py-4">
                    @if($invoice->status === 'paid')
                        <div class="text-5xl mb-2">✅</div>
                        <span class="badge badge-success text-base px-4 py-2">Paid</span>
                        @if($invoice->paid_at)
                            <p class="text-sm mt-2" style="color: var(--color-text-secondary)">Paid on {{ \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y') }}</p>
                        @endif
                    @elseif($invoice->status === 'overdue')
                        <div class="text-5xl mb-2">⚠️</div>
                        <span class="badge badge-danger text-base px-4 py-2">Overdue</span>
                    @else
                        <div class="text-5xl mb-2">⏳</div>
                        <span class="badge badge-warning text-base px-4 py-2">Pending</span>
                        @if($invoice->due_date)
                            <p class="text-sm mt-2" style="color: var(--color-text-secondary)">Due {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</p>
                        @endif
                    @endif
                </div>
            </div>
            <!-- Notes -->
            @if($invoice->notes)
            <div class="card">
                <h3 class="font-bold mb-2" style="color: var(--color-text-primary)">Notes</h3>
                <p class="text-sm" style="color: var(--color-text-secondary)">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
