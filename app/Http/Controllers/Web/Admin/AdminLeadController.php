<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminLeadController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $leads = $this->fetchDotNetLeads($request, $perPage);

        return view('admin.leads.index', compact('leads'));
    }

    public function show(int $lead)
    {
        $leadEntity = $this->fetchAllDotNetLeads()->firstWhere('id', $lead);

        if (!$leadEntity) {
            abort(404);
        }

        return view('admin.leads.show', ['lead' => $leadEntity]);
    }

    private function fetchDotNetLeads(Request $request, int $perPage): LengthAwarePaginator
    {
        $items = $this->fetchAllDotNetLeads();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $needle = strtolower($search);
            $items = $items->filter(function (object $item) use ($needle): bool {
                return str_contains(strtolower((string) $item->company_name), $needle)
                    || str_contains(strtolower((string) $item->lead_code), $needle)
                    || str_contains(strtolower((string) $item->contact_name), $needle)
                    || str_contains(strtolower((string) $item->contact_email), $needle)
                    || str_contains(strtolower((string) $item->city), $needle)
                    || str_contains(strtolower((string) $item->country), $needle)
                    || str_contains(strtolower((string) $item->state), $needle);
            })->values();
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

    private function fetchAllDotNetLeads(): Collection
    {
        $baseUrl = rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
        $token = (string) session('dotnet_access_token', '');

        if ($baseUrl === '') {
            Log::warning('Lead list: DOTNET_AUTH_API_BASE_URL is not configured.');
            return collect();
        }

        $verifySsl = (bool) config('wrkplan.auth.dotnet_verify_ssl', true);

        $http = Http::acceptJson()->withOptions(['verify' => $verifySsl])->timeout(20);
        if ($token !== '') {
            $http = $http->withToken($token);
        }

        $response = $this->postDotNetWithApiFallback(
            $http,
            $baseUrl,
            'CustomerPortal/CustomerPortal/GetLeadList',
            []
        );

        if (!$response->successful()) {
            Log::warning('Lead list endpoint failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return collect();
        }

        $rows = $response->json();
        if (!is_array($rows)) {
            return collect();
        }

        return collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): object => $this->mapLeadRow($row))
            ->sortByDesc(fn (object $lead) => $lead->created_at?->timestamp ?? 0)
            ->values();
    }

    private function mapLeadRow(array $row): object
    {
        return (object) [
            'id' => (int) ($row['ID_LEAD_COMPANY'] ?? $row['iD_LEAD_COMPANY'] ?? $row['id_lead_company'] ?? 0),
            'lead_code' => (string) ($row['lead_code'] ?? ''),
            'company_name' => (string) ($row['company_name'] ?? ''),
            'city' => (string) ($row['city'] ?? ''),
            'country' => (string) ($row['country'] ?? ''),
            'state' => (string) ($row['state'] ?? ''),
            'contact_name' => (string) ($row['contact_name'] ?? '—'),
            'contact_email' => (string) ($row['contact_email'] ?? '—'),
            'contact_title' => (string) ($row['contact_title'] ?? ''),
            'contact_phone' => (string) ($row['contact_phone'] ?? ''),
            'contact_fax' => (string) ($row['contact_fax'] ?? ''),
            'contact_description' => (string) ($row['contact_description'] ?? ''),
            'address_line1' => (string) ($row['address_line1'] ?? ''),
            'address_line2' => (string) ($row['address_line2'] ?? ''),
            'zip_code' => (string) ($row['zip_code'] ?? ''),
            'source_name' => (string) ($row['source_name'] ?? ''),
            'company_size' => (string) ($row['company_size'] ?? ''),
            'milestone_id' => $row['milestone_id'] ?? null,
            'source_id' => $row['source_id'] ?? null,
            'product_id' => $row['product_id'] ?? null,
            'created_by' => (string) ($row['created_by'] ?? ''),
            'updated_by' => (string) ($row['updated_by'] ?? ''),
            'created_at' => $this->parseDate((string) ($row['created_at'] ?? '')),
            'updated_at' => $this->parseDate((string) ($row['updated_at'] ?? '')),
            'contact_created_at' => $this->parseDate((string) ($row['contact_created_at'] ?? '')),
            'contact_updated_at' => $this->parseDate((string) ($row['contact_updated_at'] ?? '')),
        ];
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y/m/d', $value);
        } catch (\Throwable) {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }
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
}
