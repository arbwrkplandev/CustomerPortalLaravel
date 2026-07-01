<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Admin\InvoiceService;
use Illuminate\Support\Facades\Auth;

class CustomerInvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    public function index()
    {
        $invoices = Invoice::where('tenant_id', Auth::user()->tenant_id)
            ->with(['subscription.plan'])->latest()->paginate(10);
        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(Invoice $invoice)
    {
        abort_unless($invoice->tenant_id === Auth::user()->tenant_id, 403);
        $invoice->load(['subscription.plan', 'payments', 'tenant']);
        return view('customer.invoices.show', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        abort_unless($invoice->tenant_id === Auth::user()->tenant_id, 403);
        $path = $this->invoiceService->generatePdf($invoice);
        return \Illuminate\Support\Facades\Storage::download($path);
    }
}
