<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPlanController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request): View
    {
        $response = $this->api->get('/admin/plans', [
            'search' => $request->query('search'),
            'is_active' => $request->filled('status') ? ($request->string('status')->toString() === 'active' ? 'true' : 'false') : null,
            'per_page' => 12,
            'page' => $request->integer('page', 1),
        ]);

        $plans = $this->api->toPaginator($response, 12);

        return view('admin.plans.index', compact('plans'));
    }

    public function create(): View
    {
        return view('admin.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1|max:100000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $response = $this->api->post('/admin/plans', array_merge($data, [
            'is_active' => $request->boolean('is_active', true),
        ]));

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        $plan = $this->api->toEntities($response['data'] ?? []);

        return redirect()->route('admin.plans.edit', $plan->id)->with('success', 'Plan created successfully.');
    }

    public function edit(int $plan): View
    {
        $response = $this->api->get('/admin/plans/' . $plan);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $plan = $this->api->toEntities($response['data'] ?? []);
        return view('admin.plans.edit', compact('plan'));
    }

    public function update(Request $request, int $plan): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'monthly_price' => 'required|numeric|min:0',
            'quarterly_price' => 'required|numeric|min:0',
            'annual_price' => 'required|numeric|min:0',
            'max_users' => 'required|integer|min:1|max:100000',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'nullable|boolean',
        ]);

        $response = $this->api->put('/admin/plans/' . $plan, array_merge($data, [
            'is_active' => $request->boolean('is_active', false),
        ]));

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Plan updated successfully.');
    }

    public function toggleStatus(int $plan): RedirectResponse
    {
        $response = $this->api->post('/admin/plans/' . $plan . '/toggle-status');

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response));
        }

        return back()->with('success', 'Plan status updated.');
    }
}
