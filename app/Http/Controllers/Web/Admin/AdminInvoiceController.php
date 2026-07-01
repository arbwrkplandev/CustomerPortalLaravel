<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Services\Admin\InvoiceService;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function __construct(protected InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        $invoices = $this->invoiceService->list($request->only(['search', 'tenant_id', 'status']));
        $tenants = Tenant::select('id', 'company_name')->get();
        return view('admin.invoices.index', compact('invoices', 'tenants'));
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->get();
        return view('admin.invoices.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate(['tenant_id' => 'required|exists:tenants,id', 'total_amount' => 'required|numeric|min:0']);
        $invoice = $this->invoiceService->create($request->all());
        return redirect()->route('admin.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['tenant', 'subscription.plan', 'payments']);
        return view('admin.invoices.show', compact('invoice'));
    }

    public function recordPayment(Request $request, Invoice $invoice)
    {
        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:online,bank_transfer,cheque,cash,manual',
        ]);
        $this->invoiceService->recordPayment($invoice, $request->all());
        return back()->with('success', 'Payment recorded.');
    }

    public function downloadPdf(Invoice $invoice)
    {
        $path = $this->invoiceService->generatePdf($invoice);
        return \Illuminate\Support\Facades\Storage::download($path);
    }
}
