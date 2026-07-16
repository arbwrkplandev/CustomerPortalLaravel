<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminTenantController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $tenants = $this->fetchDotNetCustomers($request, $perPage);

        return view('admin.tenants.index', compact('tenants'));
    }

    private function fetchDotNetCustomers(Request $request, int $perPage): LengthAwarePaginator
    {
        $baseUrl = rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
        $token = (string) session('dotnet_access_token', '');

        if ($baseUrl === '') {
            Log::warning('Customer list: DOTNET_AUTH_API_BASE_URL is not configured.');
            return $this->emptyTenantPaginator($perPage);
        }

        $verifySsl = (bool) config('wrkplan.auth.dotnet_verify_ssl', true);

        $http = Http::acceptJson()->withOptions(['verify' => $verifySsl])->timeout(20);
        if ($token !== '') {
            $http = $http->withToken($token);
        }

        $response = $this->postDotNetWithApiFallback(
            $http,
            $baseUrl,
            'CustomerPortal/CustomerPortal/GetCustomerList',
            []
        );

        if (!$response->successful()) {
            Log::warning('Customer list endpoint failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return $this->emptyTenantPaginator($perPage);
        }

        $rows = $response->json();
        if (!is_array($rows)) {
            return $this->emptyTenantPaginator($perPage);
        }

        $items = collect($rows)->map(function (array $row): object {
            $status = strtolower((string) ($row['status'] ?? 'inactive'));
            if ($status === 'inactive') {
                $status = 'inactive';
            }

            $createdAtRaw = (string) ($row['created_at'] ?? '');
            $createdAt = now();
            if ($createdAtRaw !== '') {
                try {
                    $createdAt = Carbon::createFromFormat('Y/m/d', $createdAtRaw);
                } catch (\Throwable) {
                    try {
                        $createdAt = Carbon::parse($createdAtRaw);
                    } catch (\Throwable) {
                        $createdAt = now();
                    }
                }
            }

            return (object) [
                'id' => (int) ($row['ID_CUSTOMER_VENDOR'] ?? $row['iD_CUSTOMER_VENDOR'] ?? $row['id_customer_vendor'] ?? 0),
                'company_name' => (string) ($row['company_name'] ?? ''),
                'company_code' => (string) ($row['customer_code'] ?? $row['vendor_code'] ?? ''),
                'city' => (string) ($row['city'] ?? $row['address_city'] ?? ''),
                'country' => (string) ($row['country'] ?? $row['address_country'] ?? ''),
                'contact_name' => $row['contact_name'] ?: '—',
                'contact_email' => $row['contact_email'] ?: '—',
                'status' => $status,
                'created_at' => $createdAt,
            ];
        });

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $needle = strtolower($search);
            $items = $items->filter(function (object $item) use ($needle): bool {
                return str_contains(strtolower((string) $item->company_name), $needle)
                    || str_contains(strtolower((string) $item->company_code), $needle)
                    || str_contains(strtolower((string) $item->contact_name), $needle)
                    || str_contains(strtolower((string) $item->contact_email), $needle)
                    || str_contains(strtolower((string) $item->city), $needle)
                    || str_contains(strtolower((string) $item->country), $needle);
            })->values();
        }

        $statusFilter = strtolower((string) $request->query('status', ''));
        if (in_array($statusFilter, ['active', 'inactive', 'trial', 'suspended'], true)) {
            $items = $items->filter(fn (object $item): bool => strtolower((string) $item->status) === $statusFilter)->values();
        }

        $page = max(1, $request->integer('page', 1));
        $total = $items->count();
        $paged = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $paged,
            $total,
            $perPage,
            $page,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );
    }

    private function emptyTenantPaginator(int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            1,
            [
                'path' => url()->current(),
                'query' => request()->query(),
            ]
        );
    }

    private function postDotNetWithApiFallback($http, string $baseUrl, string $path, array $payload): HttpResponse
    {
        $urls = $this->buildDotNetCandidateUrls($baseUrl, $path);
        $response = $http->post($urls[0], $payload);

        if ($response->status() !== 404 || count($urls) < 2) {
            return $response;
        }

        return $http->post($urls[1], $payload);
    }

    private function buildDotNetCandidateUrls(string $baseUrl, string $path): array
    {
        $base = rtrim($baseUrl, '/');
        $path = ltrim($path, '/');

        $urls = [$base . '/' . $path];

        if (str_ends_with(strtolower($base), '/api')) {
            $urls[] = substr($base, 0, -4) . '/' . $path;
        } else {
            $urls[] = $base . '/api/' . $path;
        }

        return array_values(array_unique($urls));
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

    public function details(int $tenant)
    {
        $baseUrl = rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
        $token = (string) session('dotnet_access_token', '');

        if ($baseUrl === '') {
            return response()->json([
                'success' => false,
                'message' => 'DOTNET_AUTH_API_BASE_URL is not configured.',
            ], 500);
        }

        $verifySsl = (bool) config('wrkplan.auth.dotnet_verify_ssl', true);

        $http = Http::acceptJson()->withOptions(['verify' => $verifySsl])->timeout(20);
        if ($token !== '') {
            $http = $http->withToken($token);
        }

        $path = 'CustomerPortal/CustomerPortal/GetCustomerDetails?' . http_build_query([
            'customerId' => $tenant,
        ]);

        $response = $this->postDotNetWithApiFallback($http, $baseUrl, $path, []);

        if (!$response->successful()) {
            Log::warning('Customer details endpoint failed', [
                'tenant' => $tenant,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch customer details from ERP API.',
            ], $response->status() ?: 500);
        }

        $payload = $response->json();

        if (!is_array($payload)) {
            return response()->json([
                'success' => false,
                'message' => 'Unexpected customer details response format.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
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
