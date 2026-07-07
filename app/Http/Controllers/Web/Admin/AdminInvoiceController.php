<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminInvoiceController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $invoicesResponse = $this->api->get('/admin/invoices', [
            'search' => $request->query('search'),
            'tenant_id' => $request->query('tenant_id'),
            'status' => $request->query('status'),
            'per_page' => 15,
            'page' => $request->integer('page', 1),
        ]);

        $tenantsResponse = $this->api->get('/admin/tenants', [
            'per_page' => 200,
        ]);

        $invoices = $this->api->toPaginator($invoicesResponse, 15);
        $tenants = $this->api->toEntities($tenantsResponse['data'] ?? []);

        return view('admin.invoices.index', compact('invoices', 'tenants'));
    }

    public function create()
    {
        $tenantsResponse = $this->api->get('/admin/tenants', [
            'status' => 'active',
            'per_page' => 200,
        ]);
        $tenants = $this->api->toEntities($tenantsResponse['data'] ?? []);

        return view('admin.invoices.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate(['tenant_id' => 'required|integer', 'total_amount' => 'required|numeric|min:0']);

        $response = $this->api->post('/admin/invoices', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        $invoice = $this->api->toEntities($response['data'] ?? []);
        return redirect()->route('admin.invoices.show', $invoice->id)->with('success', 'Invoice created.');
    }

    public function show(int $invoice)
    {
        $response = $this->api->get('/admin/invoices/' . $invoice);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $invoice = $this->api->toEntities($response['data'] ?? []);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function recordPayment(Request $request, int $invoice)
    {
        $request->validate([
            'amount'       => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:online,bank_transfer,cheque,cash,manual',
        ]);

        $response = $this->api->post('/admin/invoices/' . $invoice . '/payment', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Payment recorded.');
    }

    public function downloadPdf(int $invoice)
    {
        return $this->api->forward('GET', '/admin/invoices/' . $invoice . '/download-pdf', asJson: false);
    }
}
