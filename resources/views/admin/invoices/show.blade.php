@extends('layouts.app')
@section('title', 'Invoice ' . $invoice->invoice_number)
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline">← Invoices</a>
        <div class="flex-1">
            <h1 class="text-2xl font-black" style="color: var(--color-text-primary)">{{ $invoice->invoice_number }}</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">{{ $invoice->tenant->company_name ?? $invoice->tenant->name }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline">⬇️ PDF</a>
            @if($invoice->status !== 'paid')
            <button onclick="document.getElementById('paymentModal').classList.remove('hidden')" class="btn btn-success">
                💰 Record Payment
            </button>
            @endif
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 rounded-xl" style="background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3)">
        <p class="text-green-400">{{ session('success') }}</p>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="card">
                <h3 class="font-bold mb-4" style="color: var(--color-text-primary)">Invoice Details</h3>
                @if($invoice->line_items)
                <table class="data-table mb-4">
                    <thead><tr><th>Description</th><th class="text-right">Amount</th></tr></thead>
                    <tbody>
                        @foreach($invoice->line_items as $item)
                        <tr>
                            <td style="color: var(--color-text-secondary)">{{ $item->description ?? 'Service' }}</td>
                            <td class="text-right" style="color: var(--color-text-primary)">${{ number_format($item->amount ?? 0, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
                <div class="border-t pt-4" style="border-color: var(--color-border)">
                    @if($invoice->tax_amount)
                    <div class="flex justify-between text-sm mb-2">
                        <span style="color: var(--color-text-secondary)">Tax</span>
                        <span>${{ number_format($invoice->tax_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between font-bold text-xl">
                        <span style="color: var(--color-text-primary)">Total</span>
                        <span style="color: var(--color-brand-primary)">${{ number_format($invoice->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>
            @if($invoice->notes)
            <div class="card">
                <h3 class="font-bold mb-2" style="color: var(--color-text-primary)">Notes</h3>
                <p class="text-sm whitespace-pre-wrap" style="color: var(--color-text-secondary)">{{ $invoice->notes }}</p>
            </div>
            @endif
        </div>

        <div class="space-y-4">
            <div class="card">
                <h3 class="font-bold mb-3" style="color: var(--color-text-primary)">Status</h3>
                <div class="text-center py-4">
                    @if($invoice->status === 'paid')
                        <div class="text-4xl mb-2">✅</div>
                        <span class="badge badge-success text-base px-4 py-2">Paid</span>
                        @if($invoice->paid_at)
                            <p class="text-sm mt-2" style="color: var(--color-text-secondary)">{{ \Carbon\Carbon::parse($invoice->paid_at)->format('M d, Y') }}</p>
                        @endif
                    @elseif($invoice->status === 'overdue')
                        <div class="text-4xl mb-2">⚠️</div>
                        <span class="badge badge-danger text-base px-4 py-2">Overdue</span>
                    @else
                        <div class="text-4xl mb-2">⏳</div>
                        <span class="badge badge-warning text-base px-4 py-2">{{ ucfirst($invoice->status) }}</span>
                    @endif
                </div>
                @if($invoice->due_date)
                <div class="text-center text-sm mt-2" style="color: var(--color-text-secondary)">
                    Due {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.6)">
    <div class="card w-full max-w-md mx-4">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold" style="color: var(--color-text-primary)">Record Payment</h3>
            <button onclick="document.getElementById('paymentModal').classList.add('hidden')" class="text-gray-400 hover:text-white">✕</button>
        </div>
        <form method="POST" action="{{ route('admin.invoices.payment', $invoice) }}" class="space-y-4">
            @csrf
            <div>
                <label class="form-label">Amount Paid ($)</label>
                <input type="number" name="amount" value="{{ $invoice->total_amount }}" step="0.01" required class="form-input">
            </div>
            <div>
                <label class="form-label">Payment Method</label>
                <select name="method" class="form-input">
                    @foreach(['bank_transfer', 'credit_card', 'cheque', 'cash', 'other'] as $m)
                    <option value="{{ $m }}">{{ ucwords(str_replace('_', ' ', $m)) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Payment Date</label>
                <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="form-input">
            </div>
            <div>
                <label class="form-label">Reference</label>
                <input type="text" name="reference" class="form-input" placeholder="Transaction ID or cheque number">
            </div>
            <div class="flex gap-4">
                <button type="submit" class="btn btn-success flex-1">Confirm Payment</button>
                <button type="button" onclick="document.getElementById('paymentModal').classList.add('hidden')" class="btn btn-outline">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection
