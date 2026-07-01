<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\TenantService;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Admin - Tenants", description="Tenant/Customer management")
 */
class TenantController extends Controller
{
    use ApiResponse;

    public function __construct(protected TenantService $tenantService) {}

    /**
     * @OA\Get(path="/api/v1/admin/tenants", tags={"Admin - Tenants"}, summary="List all tenants",
     *   @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="status", in="query", @OA\Schema(type="string")),
     *   @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Paginated tenant list")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $tenants = $this->tenantService->list(
            $request->only(['search', 'status', 'sort', 'direction']),
            $request->integer('per_page', 15)
        );
        return $this->paginated($tenants);
    }

    /**
     * @OA\Post(path="/api/v1/admin/tenants", tags={"Admin - Tenants"}, summary="Create a new tenant/customer",
     *   @OA\RequestBody(required=true, @OA\JsonContent(required={"company_name","contact_name","contact_email"},
     *     @OA\Property(property="company_name", type="string"),
     *     @OA\Property(property="contact_name", type="string"),
     *     @OA\Property(property="contact_email", type="string"),
     *     @OA\Property(property="password", type="string"),
     *     @OA\Property(property="status", type="string", example="trial")
     *   )),
     *   @OA\Response(response=201, description="Tenant created")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name'  => 'required|string|max:255',
            'contact_name'  => 'required|string|max:255',
            'contact_email' => 'required|email|unique:tenants,contact_email|unique:users,email',
            'contact_phone' => 'nullable|string|max:30',
            'address'       => 'nullable|string',
            'city'          => 'nullable|string|max:100',
            'country'       => 'nullable|string|max:100',
            'status'        => 'nullable|in:active,inactive,trial',
            'password'      => 'nullable|string|min:6',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $tenant = $this->tenantService->create($request->validated());
        return $this->created($tenant, 'Customer created successfully');
    }

    /**
     * @OA\Get(path="/api/v1/admin/tenants/{id}", tags={"Admin - Tenants"}, summary="Get tenant details",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tenant details")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $tenant = Tenant::with([
            'users', 'subscriptions.plan', 'contracts', 'invoices'
        ])->findOrFail($id);
        return $this->success($tenant);
    }

    /**
     * @OA\Put(path="/api/v1/admin/tenants/{id}", tags={"Admin - Tenants"}, summary="Update tenant",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Tenant updated")
     * )
     */
    public function update(Request $request, Tenant $tenant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'company_name'  => 'nullable|string|max:255',
            'contact_name'  => 'nullable|string|max:255',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:30',
            'status'        => 'nullable|in:active,inactive,suspended,trial',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $tenant = $this->tenantService->update($tenant, $request->all());
        return $this->success($tenant, 'Tenant updated successfully');
    }

    /**
     * @OA\Post(path="/api/v1/admin/tenants/{id}/toggle-status", tags={"Admin - Tenants"}, summary="Toggle active/inactive",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\Response(response=200, description="Status toggled")
     * )
     */
    public function toggleStatus(Tenant $tenant): JsonResponse
    {
        $tenant = $this->tenantService->toggleStatus($tenant);
        return $this->success($tenant, 'Status updated');
    }

    /**
     * @OA\Post(path="/api/v1/admin/tenants/{id}/assign-subscription", tags={"Admin - Tenants"}, summary="Assign subscription plan",
     *   @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *   @OA\RequestBody(required=true, @OA\JsonContent(required={"plan_id","billing_cycle"},
     *     @OA\Property(property="plan_id", type="integer"),
     *     @OA\Property(property="billing_cycle", type="string", example="monthly"),
     *     @OA\Property(property="start_date", type="string", format="date")
     *   )),
     *   @OA\Response(response=201, description="Subscription assigned")
     * )
     */
    public function assignSubscription(Request $request, Tenant $tenant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'plan_id'      => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,quarterly,annual',
            'start_date'   => 'nullable|date',
            'notes'        => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $subscription = $this->tenantService->assignSubscription($tenant, $request->all());
        return $this->created($subscription, 'Subscription assigned successfully');
    }
}
