<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;

class CustomerInvoiceController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $response = $this->api->get('/customer/invoices', [
            'per_page' => 10,
            'page' => request()->integer('page', 1),
            'status' => request()->query('status'),
        ]);

        $invoices = $this->api->toPaginator($response, 10);

        return view('customer.invoices.index', compact('invoices'));
    }

    public function show(int $invoice)
    {
        $response = $this->api->get('/customer/invoices/' . $invoice);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $invoice = $this->api->toEntities($response['data'] ?? []);

        return view('customer.invoices.show', compact('invoice'));
    }

    public function download(int $invoice)
    {
        return redirect('/api/v1/customer/invoices/' . $invoice . '/download');
    }
}
