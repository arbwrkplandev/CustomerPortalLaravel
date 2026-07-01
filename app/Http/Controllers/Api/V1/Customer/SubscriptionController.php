<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\CustomerSubscription;
use App\Services\Admin\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * @OA\Tag(name="Customer - Subscriptions & Invoices", description="Subscription and billing")
 */
class SubscriptionController extends Controller
{
    use ApiResponse;

    public function __construct(protected InvoiceService $invoiceService) {}

    public function currentSubscription(): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $subscription = CustomerSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with(['plan', 'invoices'])
            ->latest()
            ->first();

        return $this->success($subscription);
    }

    public function plans(): JsonResponse
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        return $this->success($plans);
    }

    public function invoices(Request $request): JsonResponse
    {
        $invoices = Invoice::where('tenant_id', Auth::user()->tenant_id)
            ->with(['subscription.plan', 'payments'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($invoices);
    }

    public function invoiceDetail(int $id): JsonResponse
    {
        $invoice = Invoice::where('tenant_id', Auth::user()->tenant_id)
            ->with(['subscription.plan', 'payments', 'tenant'])
            ->findOrFail($id);
        return $this->success($invoice);
    }

    public function downloadInvoice(int $id): mixed
    {
        $invoice = Invoice::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $path = $this->invoiceService->generatePdf($invoice);
        return \Illuminate\Support\Facades\Storage::download($path);
    }

    /**
     * Calculate renewal pricing preview based on current plan expiry.
     */
    public function renewalPreview(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $subscription = CustomerSubscription::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('plan')
            ->latest()
            ->firstOrFail();

        $cycle = $request->get('billing_cycle', $subscription->billing_cycle);
        $plan = $subscription->plan;
        $startDate = Carbon::parse($subscription->end_date)->addDay();
        $endDate = match ($cycle) {
            'monthly'   => $startDate->copy()->addMonth(),
            'quarterly' => $startDate->copy()->addMonths(3),
            'annual'    => $startDate->copy()->addYear(),
            default     => $startDate->copy()->addMonth(),
        };

        return $this->success([
            'plan'          => $plan,
            'billing_cycle' => $cycle,
            'start_date'    => $startDate->toDateString(),
            'end_date'      => $endDate->toDateString(),
            'amount'        => $plan->getPriceForCycle($cycle),
            'currency'      => $subscription->currency,
        ]);
    }
}
