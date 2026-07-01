<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use App\Models\CustomerSubscription;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Invoice::with(['tenant', 'subscription.plan']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%$s%");
            })->orWhereHas('tenant', fn($q) => $q->where('company_name', 'like', "%$s%"));
        }
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id'       => $data['tenant_id'],
            'subscription_id' => $data['subscription_id'] ?? null,
            'invoice_number'  => $this->generateInvoiceNumber(),
            'status'          => 'draft',
            'issue_date'      => $data['issue_date'] ?? now()->toDateString(),
            'due_date'        => $data['due_date'] ?? now()->addDays(30)->toDateString(),
            'subtotal'        => $data['subtotal'] ?? 0,
            'tax_amount'      => $data['tax_amount'] ?? 0,
            'discount_amount' => $data['discount_amount'] ?? 0,
            'total_amount'    => $data['total_amount'] ?? 0,
            'currency'        => $data['currency'] ?? 'USD',
            'line_items'      => $data['line_items'] ?? [],
            'notes'           => $data['notes'] ?? null,
            'created_by'      => auth()->id(),
        ]);

        return $invoice->load(['tenant', 'subscription']);
    }

    public function generateFromSubscription(CustomerSubscription $subscription): Invoice
    {
        $plan = $subscription->plan;
        $amount = $subscription->amount;
        $tax = round($amount * config('wrkplan.invoice.tax_rate', 0), 2);

        return $this->create([
            'tenant_id'       => $subscription->tenant_id,
            'subscription_id' => $subscription->id,
            'issue_date'      => now()->toDateString(),
            'due_date'        => now()->addDays(30)->toDateString(),
            'subtotal'        => $amount,
            'tax_amount'      => $tax,
            'total_amount'    => $amount + $tax,
            'currency'        => $subscription->currency,
            'line_items'      => [
                [
                    'description' => $plan->name . ' (' . ucfirst($subscription->billing_cycle) . ')',
                    'quantity'    => 1,
                    'unit_price'  => $amount,
                    'total'       => $amount,
                ]
            ],
        ]);
    }

    public function recordPayment(Invoice $invoice, array $data): Payment
    {
        $payment = Payment::create([
            'tenant_id'         => $invoice->tenant_id,
            'invoice_id'        => $invoice->id,
            'subscription_id'   => $invoice->subscription_id,
            'payment_reference' => 'PAY-' . strtoupper(Str::random(8)),
            'amount'            => $data['amount'],
            'currency'          => $data['currency'] ?? $invoice->currency,
            'payment_mode'      => $data['payment_mode'],
            'status'            => 'completed',
            'payment_date'      => $data['payment_date'] ?? now()->toDateString(),
            'transaction_id'    => $data['transaction_id'] ?? null,
            'notes'             => $data['notes'] ?? null,
            'recorded_by'       => auth()->id(),
        ]);

        // Update invoice status
        if ($payment->amount >= $invoice->total_amount) {
            $invoice->update(['status' => 'paid', 'paid_date' => $data['payment_date'] ?? now()]);
        }

        return $payment;
    }

    public function generatePdf(Invoice $invoice): string
    {
        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice->load(['tenant', 'subscription.plan'])]);
        $path = 'invoices/' . $invoice->invoice_number . '.pdf';
        Storage::put($path, $pdf->output());
        $invoice->update(['pdf_path' => $path]);
        return $path;
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = config('wrkplan.invoice.prefix', 'INV');
        $year = now()->format('Y');
        $count = Invoice::whereYear('created_at', $year)->count() + 1;
        return "{$prefix}-{$year}-" . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
