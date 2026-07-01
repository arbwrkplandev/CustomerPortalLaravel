@extends('layouts.app')
@section('title', 'Invoices')
@section('portal-name', 'Admin Hub')

@section('sidebar-nav')
    @include('layouts.admin-sidebar')
@endsection

@section('content')
<div class="animate-fadeInUp">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-black" style="color: var(--color-text-primary)">Invoices</h1>
            <p class="mt-1" style="color: var(--color-text-secondary)">Manage billing and payments</p>
        </div>
        <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Invoice
        </a>
    </div>

    <!-- Filters -->
    <div class="card mb-6">
        <form method="GET" class="flex flex-wrap gap-4">
            <input type="text" name="search" value="{{ request('search') }}" class="form-input flex-1 min-w-48" placeholder="Invoice # or customer...">
            <select name="status" class="form-input w-36">
                <option value="">All Status</option>
                @foreach(['draft', 'pending', 'paid', 'overdue', 'cancelled'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline">Filter</button>
            @if(request()->anyFilled(['search', 'status'])) <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline">Clear</a> @endif
        </form>
    </div>

    @if($invoices->isEmpty())
        <div class="card text-center py-16">
            <h3 class="text-lg font-semibold" style="color: var(--color-text-primary)">No invoices found</h3>
            <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary mt-4">Create First Invoice</a>
        </div>
    @else
        <div class="card overflow-hidden">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td><span class="font-mono font-semibold" style="color: var(--color-brand-primary)">{{ $invoice->invoice_number }}</span></td>
                        <td style="color: var(--color-text-secondary)">{{ $invoice->tenant->name }}</td>
                        <td><span class="font-bold" style="color: var(--color-text-primary)">${{ number_format($invoice->total_amount, 2) }}</span></td>
                        <td>
                            @if($invoice->status === 'paid') <span class="badge badge-success">Paid</span>
                            @elseif($invoice->status === 'overdue') <span class="badge badge-danger">Overdue</span>
                            @elseif($invoice->status === 'pending') <span class="badge badge-warning">Pending</span>
                            @else <span class="badge" style="background: var(--color-surface-2); color: var(--color-text-secondary)">{{ ucfirst($invoice->status) }}</span>
                            @endif
                        </td>
                        <td style="color: var(--color-text-secondary)">
                            {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : '—' }}
                        </td>
                        <td class="text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-outline btn-sm">View</a>
                                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-outline btn-sm">PDF</a>
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
