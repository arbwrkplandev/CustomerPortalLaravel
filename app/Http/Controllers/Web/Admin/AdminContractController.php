<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminContractController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 15);
        $agreements = $this->fetchDotNetContractAgreements($request, $perPage);

        return view('admin.contracts.index', compact('agreements'));
    }

    public function create()
    {
        return view('admin.contracts.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'document_type' => 'required|string|max:255',
            'document_title' => 'required|string|max:255',
            'document_content' => 'required|string',
        ]);

        $payload = [
            'document_type' => (string) $request->input('document_type'),
            'document_title' => (string) $request->input('document_title'),
            'document_content' => (string) $request->input('document_content'),
        ];

        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/CreateContractAgreement',
            $payload
        );

        if (!$response->successful()) {
            return back()
                ->withErrors(['api' => $this->extractDotNetError($response)])
                ->withInput();
        }

        $json = $response->json();
        $agreementId = is_array($json) ? (int) ($json['id_contract_agreement'] ?? 0) : 0;

        if ($agreementId > 0) {
            return redirect()->route('admin.contracts.show', $agreementId)
                ->with('success', 'Contract agreement created successfully.');
        }

        return redirect()->route('admin.contracts.index')
            ->with('success', 'Contract agreement created successfully.');
    }

    public function show(int $contract)
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/GetContractAgreementDetail?agreementId=' . $contract,
            []
        );

        if (!$response->successful()) {
            if ($response->status() === 404) {
                abort(404);
            }

            abort(500, $this->extractDotNetError($response));
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            abort(500, 'Unexpected agreement response format.');
        }

        $agreement = $this->mapAgreementDetail($payload);

        return view('admin.contracts.show', compact('agreement'));
    }

    public function update(Request $request, int $contract)
    {
        $request->validate([
            'document_type' => 'required|string|max:255',
            'document_title' => 'required|string|max:255',
            'document_content' => 'required|string',
        ]);

        $payload = [
            'document_type' => (string) $request->input('document_type'),
            'document_title' => (string) $request->input('document_title'),
            'document_content' => (string) $request->input('document_content'),
        ];

        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/UpdateContractAgreement?agreementId=' . $contract,
            $payload
        );

        if (!$response->successful()) {
            return back()
                ->withErrors(['api' => $this->extractDotNetError($response)])
                ->withInput();
        }

        return redirect()->route('admin.contracts.show', $contract)
            ->with('success', 'Contract agreement updated successfully.');
    }

    public function destroy(int $contract)
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/DeleteContractAgreement?agreementId=' . $contract,
            []
        );

        if (!$response->successful()) {
            return back()->withErrors(['api' => $this->extractDotNetError($response)]);
        }

        return redirect()->route('admin.contracts.index')
            ->with('success', 'Contract agreement deleted successfully.');
    }

    private function fetchDotNetContractAgreements(Request $request, int $perPage): LengthAwarePaginator
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/GetContractAgreementList',
            []
        );

        if (!$response->successful()) {
            Log::warning('Contract agreement list endpoint failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return $this->emptyAgreementPaginator($request, $perPage);
        }

        $rows = $response->json();
        if (!is_array($rows)) {
            return $this->emptyAgreementPaginator($request, $perPage);
        }

        $items = collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(fn (array $row): object => $this->mapAgreementRow($row))
            ->sortByDesc(fn (object $item) => $item->created_at?->timestamp ?? 0)
            ->values();

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $needle = strtolower($search);
            $items = $items->filter(function (object $item) use ($needle): bool {
                return str_contains(strtolower((string) $item->document_type), $needle)
                    || str_contains(strtolower((string) $item->document_title), $needle)
                    || str_contains(strtolower((string) $item->created_by), $needle)
                    || str_contains((string) $item->id, $needle);
            })->values();
        }

        $tagged = strtolower((string) $request->query('tagged', ''));
        if (in_array($tagged, ['yes', 'no'], true)) {
            $needTagged = $tagged === 'yes';
            $items = $items->filter(fn (object $item): bool => (bool) $item->is_tagged === $needTagged)->values();
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

    private function emptyAgreementPaginator(Request $request, int $perPage): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            1,
            [
                'path' => url()->current(),
                'query' => $request->query(),
            ]
        );
    }

    private function mapAgreementRow(array $row): object
    {
        return (object) [
            'id' => (int) ($row['iD_CONTRACT_AGREEMENT'] ?? $row['ID_CONTRACT_AGREEMENT'] ?? 0),
            'id_company' => (int) ($row['iD_COMPANY'] ?? $row['ID_COMPANY'] ?? 0),
            'document_type' => (string) ($row['document_type'] ?? ''),
            'document_title' => (string) ($row['document_title'] ?? ''),
            'content_length' => (int) ($row['content_length'] ?? 0),
            'is_compressed' => (bool) ($row['is_compressed'] ?? false),
            'created_at' => $this->parseDate((string) ($row['created_at'] ?? '')),
            'created_by' => (string) ($row['created_by'] ?? ''),
            'updated_at' => $this->parseDate((string) ($row['updated_at'] ?? '')),
            'updated_by' => (string) ($row['updated_by'] ?? ''),
            'is_tagged' => (bool) ($row['is_tagged'] ?? false),
        ];
    }

    private function mapAgreementDetail(array $row): object
    {
        $agreement = $this->mapAgreementRow($row);
        $agreement->document_content = (string) ($row['document_content'] ?? '');

        return $agreement;
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

    private function dotNetBaseUrl(): string
    {
        return rtrim((string) config('wrkplan.auth.dotnet_api_base_url', ''), '/');
    }

    private function dotNetHttpClient()
    {
        $baseUrl = $this->dotNetBaseUrl();
        $token = (string) session('dotnet_access_token', '');
        $verifySsl = (bool) config('wrkplan.auth.dotnet_verify_ssl', true);
        $apiKey = trim((string) config('wrkplan.auth.dotnet_api_key', ''));

        if ($baseUrl === '') {
            abort(500, 'DOTNET_AUTH_API_BASE_URL is not configured.');
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];

        if ($apiKey !== '') {
            $headers['X-API-KEY'] = $apiKey;
            $headers['X-Api-Key'] = $apiKey;
            $headers['ApiKey'] = $apiKey;
        }

        $http = Http::withOptions(['verify' => $verifySsl])
            ->withHeaders($headers)
            ->timeout(20);

        if ($token !== '') {
            $http = $http->withToken($token);
        }

        return $http;
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

    private function extractDotNetError(HttpResponse $response): string
    {
        $json = $response->json();
        if (is_array($json) && isset($json['message']) && is_string($json['message'])) {
            return $json['message'];
        }

        $body = trim((string) $response->body());
        if ($body !== '') {
            return $body;
        }

        return 'Contract agreement request failed with status ' . $response->status() . '.';
    }
}
