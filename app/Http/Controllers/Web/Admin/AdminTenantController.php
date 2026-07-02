<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminTenantController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $response = $this->api->get('/admin/tenants', [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'per_page' => $perPage,
            'page' => $request->integer('page', 1),
        ]);

        $tenants = $this->api->toPaginator($response, $perPage);

        return view('admin.tenants.index', compact('tenants'));
    }

    public function create()
    {
        $response = $this->api->get('/admin/plans', [
            'is_active' => 'true',
            'per_page' => 200,
        ]);

        $plans = $this->api->toEntities($response['data'] ?? []);

        return view('admin.tenants.create', compact('plans'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'corp_id'          => 'required|string|max:30|alpha_dash',
            'contact_name'     => 'required|string|max:255',
            'contact_email'    => 'required|email',
            'user_email'       => 'required|email',
            'username'         => 'required|string|min:3|max:50|alpha_dash',
            'contact_password' => 'required|string|min:6',
            'country'          => 'nullable|string|max:100',
            'timezone'         => 'nullable|string|max:60',
            'plan_id'          => 'nullable|integer',
            'billing_cycle'    => 'nullable|in:monthly,quarterly,annual',
            'custom_rate'      => 'nullable|numeric|min:0',
            'currency'         => 'nullable|string|size:3',
        ]);

        $payload = $request->all();
        $payload['company_name'] = $payload['name'];

        $response = $this->api->post('/admin/tenants', $payload);

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        $tenant = $this->api->toEntities($response['data'] ?? []);
        return redirect()->route('admin.tenants.show', $tenant->id)->with('success', 'Customer created successfully!');
    }

    public function show(int $tenant)
    {
        $tenantResponse = $this->api->get('/admin/tenants/' . $tenant);
        if (!($tenantResponse['success'] ?? false)) {
            abort(404);
        }

        $plansResponse = $this->api->get('/admin/plans', [
            'is_active' => 'true',
            'per_page' => 200,
        ]);

        $tenant = $this->api->toEntities($tenantResponse['data'] ?? []);
        $plans = $this->api->toEntities($plansResponse['data'] ?? []);

        return view('admin.tenants.show', compact('tenant', 'plans'));
    }

    public function edit(int $tenant)
    {
        $response = $this->api->get('/admin/tenants/' . $tenant);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $tenant = $this->api->toEntities($response['data'] ?? []);
        return view('admin.tenants.edit', compact('tenant'));
    }

    public function update(Request $request, int $tenant)
    {
        $response = $this->api->put('/admin/tenants/' . $tenant, $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Customer updated.');
    }

    public function toggleStatus(int $tenant)
    {
        $response = $this->api->post('/admin/tenants/' . $tenant . '/toggle-status');
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response));
        }

        return back()->with('success', 'Status updated.');
    }

    public function assignSubscription(Request $request, int $tenant)
    {
        $request->validate([
            'plan_id'       => 'required|integer',
            'billing_cycle' => 'required|in:monthly,quarterly,annual',
            'custom_rate'   => 'nullable|numeric|min:0',
            'currency'      => 'nullable|string|size:3',
            'notes'         => 'nullable|string',
        ]);

        $response = $this->api->post('/admin/tenants/' . $tenant . '/assign-subscription', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Subscription assigned successfully!');
    }

    public function updateSubscription(Request $request, int $tenant)
    {
        $request->validate([
            'plan_id'       => 'nullable|integer',
            'billing_cycle' => 'nullable|in:monthly,quarterly,annual',
            'custom_rate'   => 'nullable|numeric|min:0',
            'currency'      => 'nullable|string|size:3',
            'notes'         => 'nullable|string',
        ]);

        $payload = $request->only(['plan_id', 'billing_cycle', 'currency', 'notes']);
        // Pass custom_rate explicitly so null clears custom override
        $payload['custom_rate'] = $request->filled('custom_rate') ? $request->input('custom_rate') : null;

        $response = $this->api->patch('/admin/tenants/' . $tenant . '/subscription', $payload);
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Subscription updated successfully!');
    }

    public function resetUserPassword(Request $request, int $tenant, int $user)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $response = $this->api->post(
            '/admin/tenants/' . $tenant . '/users/' . $user . '/reset-password',
            $request->all()
        );

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Password updated successfully.');
    }
}
