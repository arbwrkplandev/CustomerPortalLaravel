<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Plan;
use App\Services\Admin\TenantService;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function __construct(protected TenantService $tenantService) {}

    public function index(Request $request)
    {
        $tenants = $this->tenantService->list(
            $request->only(['search', 'status']),
            $request->integer('per_page', 15)
        );
        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $plans = Plan::where('is_active', true)->get();
        return view('admin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:tenants,email',
            'contact_name'     => 'required|string|max:255',
            'contact_email'    => 'required|email|unique:users,email',
            'contact_password' => 'required|string|min:6',
        ]);

        $tenant = $this->tenantService->create($request->all());
        return redirect()->route('admin.tenants.show', $tenant)->with('success', 'Customer created successfully!');
    }

    public function show(Tenant $tenant)
    {
        $tenant->load(['users', 'subscriptions.plan', 'contracts', 'invoices.payments']);
        $plans = Plan::where('is_active', true)->get();
        return view('admin.tenants.show', compact('tenant', 'plans'));
    }

    public function edit(Tenant $tenant)
    {
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $this->tenantService->update($tenant, $request->all());
        return back()->with('success', 'Customer updated.');
    }

    public function toggleStatus(Tenant $tenant)
    {
        $this->tenantService->toggleStatus($tenant);
        return back()->with('success', 'Status updated.');
    }

    public function assignSubscription(Request $request, Tenant $tenant)
    {
        $request->validate([
            'plan_id'       => 'required|exists:plans,id',
            'billing_cycle' => 'required|in:monthly,quarterly,annual',
        ]);
        $this->tenantService->assignSubscription($tenant, $request->all());
        return back()->with('success', 'Subscription assigned successfully!');
    }
}
