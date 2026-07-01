<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\InvoiceService;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Admin - Invoices", description="Invoice and payment management")
 */
class InvoiceController extends Controller
{
    use ApiResponse;

    public function __construct(protected InvoiceService $invoiceService) {}

    public function index(Request $request): JsonResponse
    {
        $invoices = $this->invoiceService->list(
            $request->only(['search', 'tenant_id', 'status']),
            $request->integer('per_page', 15)
        );
        return $this->paginated($invoices);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id'       => 'required|exists:tenants,id',
            'subscription_id' => 'nullable|exists:customer_subscriptions,id',
            'issue_date'      => 'nullable|date',
            'due_date'        => 'nullable|date',
            'subtotal'        => 'required|numeric|min:0',
            'tax_amount'      => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'total_amount'    => 'required|numeric|min:0',
            'line_items'      => 'nullable|array',
            'notes'           => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $invoice = $this->invoiceService->create($request->all());
        return $this->created($invoice);
    }

    public function show(Invoice $invoice): JsonResponse
    {
        return $this->success($invoice->load(['tenant', 'subscription.plan', 'payments']));
    }

    public function recordPayment(Request $request, Invoice $invoice): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:0.01',
            'payment_mode'   => 'required|in:online,bank_transfer,cheque,cash,manual',
            'payment_date'   => 'nullable|date',
            'transaction_id' => 'nullable|string',
            'notes'          => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $payment = $this->invoiceService->recordPayment($invoice, $request->all());
        return $this->created($payment, 'Payment recorded successfully');
    }

    public function downloadPdf(Invoice $invoice): mixed
    {
        $path = $this->invoiceService->generatePdf($invoice);
        return \Illuminate\Support\Facades\Storage::download($path);
    }
}
