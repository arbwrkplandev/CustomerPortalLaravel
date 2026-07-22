<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\AgreementDocxBuilder;
use App\Support\CustomerAgreementStore;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminCustomerAgreementController extends Controller
{
    public function __construct(
        private readonly CustomerAgreementStore $agreementStore,
        private readonly AgreementDocxBuilder $docxBuilder,
    )
    {
    }

    public function workspace(int $tenant): JsonResponse
    {
        $customer = $this->fetchCustomerSummary($tenant);
        $agreements = $this->fetchAgreementList();
        $records = $this->agreementStore->getRecords($tenant);

        return response()->json([
            'success' => true,
            'data' => [
                'customer' => $customer,
                'merge_fields' => $this->buildMergeFields($customer),
                'agreements' => $agreements,
                'records' => $records,
            ],
        ]);
    }

    public function template(int $tenant, int $agreement): JsonResponse
    {
        $customer = $this->fetchCustomerSummary($tenant);
        $detail = $this->fetchAgreementDetail($agreement);

        $agreementType = (string) ($detail['agreement_type'] ?? $detail['document_type'] ?? 'Agreement');
        $rawContent = $this->extractAgreementContent($detail);
        $mergedContent = $this->mergeContent($rawContent, $this->buildMergeFields($customer));

        return response()->json([
            'success' => true,
            'data' => [
                'agreement_id' => $agreement,
                'agreement_type' => $agreementType,
                'content' => $mergedContent,
                'merge_fields' => $this->buildMergeFields($customer),
            ],
        ]);
    }

    public function saveDraft(Request $request, int $tenant): JsonResponse
    {
        $validated = $request->validate([
            'agreement_id' => 'required|integer|min:1',
            'edited_content' => 'nullable|string',
            'remarks' => 'nullable|string|max:500',
            'fin_year_code' => 'nullable|string|max:10',
        ]);

        $agreementId = (int) $validated['agreement_id'];
        $customer = $this->fetchCustomerSummary($tenant);
        $detail = $this->fetchAgreementDetail($agreementId);

        $agreementType = (string) ($detail['agreement_type'] ?? $detail['document_type'] ?? ('Agreement ' . $agreementId));
        $content = trim((string) ($validated['edited_content'] ?? ''));
        if ($content === '') {
            $content = $this->extractAgreementContent($detail);
        }

        $mergeFields = $this->buildMergeFields($customer);
        $content = $this->mergeContent($content, $mergeFields);

        try {
            $docxBytes = $this->docxBuilder->buildFromHtml($content);
            $fileInfo = $this->agreementStore->writeDraftFile($tenant, $agreementType, $docxBytes, 'docx');
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 500);
        }

        $record = $this->agreementStore->upsertRecord($tenant, [
            'id' => (string) Str::uuid(),
            'customer_id' => $tenant,
            'agreement_id' => $agreementId,
            'agreement_type' => $agreementType,
            'status' => 'draft',
            'content' => $content,
            'remarks' => (string) ($validated['remarks'] ?? ''),
            'fin_year_code' => (string) ($validated['fin_year_code'] ?? now()->format('Y')),
            'tagged_agreement_location' => $fileInfo['relative_location'],
            'file_name' => $fileInfo['file_name'],
            'sent_at' => null,
            'customer_acknowledged_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Draft saved successfully.',
            'data' => [
                'record' => $record,
            ],
        ]);
    }

    public function send(Request $request, int $tenant): JsonResponse
    {
        $validated = $request->validate([
            'draft_ids' => 'required|array|min:1',
            'draft_ids.*' => 'required|string',
            'remarks' => 'nullable|string|max:500',
            'fin_year_code' => 'nullable|string|max:10',
        ]);

        $draftIds = array_values(array_unique(array_map('strval', $validated['draft_ids'])));
        $records = [];
        foreach ($draftIds as $draftId) {
            $record = $this->agreementStore->findRecord($tenant, $draftId);
            if ($record === null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Draft not found: ' . $draftId,
                ], 404);
            }

            $records[] = $record;
        }

        $payload = [
            'id_customer_vendor' => $tenant,
            'id_contract_agreement' => array_values(array_map(
                static fn (array $record): int => (int) ($record['agreement_id'] ?? 0),
                $records
            )),
            'fin_year_code' => (string) ($validated['fin_year_code'] ?? now()->format('Y')),
            'tagged_agreement_location' => array_values(array_map(
                static fn (array $record): string => (string) ($record['tagged_agreement_location'] ?? ''),
                $records
            )),
            'remarks' => (string) ($validated['remarks'] ?? ''),
        ];

        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/TagCustomerWithAgreement',
            $payload
        );

        if (!$response->successful()) {
            Log::warning('TagCustomerWithAgreement failed', [
                'tenant' => $tenant,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->extractDotNetError($response),
            ], $response->status() ?: 500);
        }

        $sentAt = now()->toIso8601String();
        $updatedRecords = [];
        foreach ($records as $record) {
            $updated = $this->agreementStore->updateRecord($tenant, (string) $record['id'], function (array $existing) use ($sentAt, $validated): array {
                $existing['status'] = 'sent';
                $existing['sent_at'] = $sentAt;
                if (($validated['remarks'] ?? null) !== null) {
                    $existing['remarks'] = (string) $validated['remarks'];
                }
                if (($validated['fin_year_code'] ?? null) !== null) {
                    $existing['fin_year_code'] = (string) $validated['fin_year_code'];
                }

                return $existing;
            });

            if ($updated !== null) {
                $updatedRecords[] = $updated;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Agreement sent to customer successfully.',
            'data' => [
                'records' => $updatedRecords,
                'api_response' => $response->json(),
            ],
        ]);
    }

    public function signedCopies(int $tenant): JsonResponse
    {
        $records = collect($this->agreementStore->getRecords($tenant))
            ->filter(fn (array $record): bool => in_array((string) ($record['status'] ?? ''), ['acknowledged', 'signed'], true))
            ->values()
            ->map(function (array $record) use ($tenant): array {
                $downloadReady = $this->resolveAbsoluteRecordPath((string) ($record['tagged_agreement_location'] ?? ''));

                return [
                    'id' => (string) ($record['id'] ?? ''),
                    'agreement_type' => (string) ($record['agreement_type'] ?? 'Agreement'),
                    'status' => (string) ($record['status'] ?? ''),
                    'signer_name' => (string) ($record['signature_signer_name'] ?? 'Customer'),
                    'signed_at' => (string) ($record['signature_signed_at'] ?? $record['customer_acknowledged_at'] ?? ''),
                    'sent_at' => (string) ($record['sent_at'] ?? ''),
                    'remarks' => (string) ($record['remarks'] ?? ''),
                    'fin_year_code' => (string) ($record['fin_year_code'] ?? ''),
                    'content' => (string) ($record['content'] ?? ''),
                    'signature_preview' => (string) ($record['signature_data'] ?? ''),
                    'download_ready' => $downloadReady !== null,
                    'download_url' => route('admin.tenants.agreements.signed.download', [$tenant, (string) ($record['id'] ?? '')]),
                ];
            })
            ->all();

        return response()->json([
            'success' => true,
            'data' => [
                'records' => $records,
            ],
        ]);
    }

    public function downloadSignedCopy(int $tenant, string $recordId)
    {
        $record = $this->agreementStore->findRecord($tenant, $recordId);
        if ($record === null || !in_array((string) ($record['status'] ?? ''), ['acknowledged', 'signed'], true)) {
            abort(404);
        }

        $absolutePath = $this->resolveAbsoluteRecordPath((string) ($record['tagged_agreement_location'] ?? ''));
        if ($absolutePath === null || !is_file($absolutePath)) {
            abort(404, 'Signed copy file not found.');
        }

        $downloadName = trim((string) ($record['agreement_type'] ?? 'agreement'));
        if ($downloadName === '') {
            $downloadName = 'agreement';
        }

        $safe = Str::of($downloadName)->ascii()->replaceMatches('/[^A-Za-z0-9\-_]+/', '_')->trim('_')->toString();
        if ($safe === '') {
            $safe = 'agreement';
        }

        return response()->download($absolutePath, $safe . '_signed.docx');
    }

    private function fetchAgreementList(): array
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/GetContractAgreementList',
            []
        );

        if (!$response->successful()) {
            Log::warning('Agreement list endpoint failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        $rows = $response->json();
        if (!is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(static fn ($row): bool => is_array($row))
            ->map(static function (array $row): array {
                return [
                    'id' => (int) ($row['iD_CONTRACT_AGREEMENT'] ?? $row['ID_CONTRACT_AGREEMENT'] ?? 0),
                    'id_company' => (int) ($row['iD_COMPANY'] ?? $row['ID_COMPANY'] ?? 0),
                    'agreement_type' => (string) ($row['agreement_type'] ?? $row['document_type'] ?? ''),
                    'agreement_location' => (string) ($row['agreement_location'] ?? $row['document_location'] ?? ''),
                    'is_tagged' => (bool) ($row['is_tagged'] ?? false),
                    'created_at' => (string) ($row['created_at'] ?? ''),
                    'updated_at' => (string) ($row['updated_at'] ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function fetchAgreementDetail(int $agreementId): array
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'Contract/GetContractAgreementDetail?agreementId=' . $agreementId,
            []
        );

        if ($response->successful()) {
            $payload = $response->json();
            if (is_array($payload)) {
                return $payload;
            }
        }

        // Endpoint compatibility fallback: newer APIs can expose template path in list only.
        $row = collect($this->fetchAgreementList())
            ->first(static fn (array $item): bool => (int) ($item['id'] ?? 0) === $agreementId);

        if (is_array($row)) {
            return [
                'iD_CONTRACT_AGREEMENT' => (int) ($row['id'] ?? $agreementId),
                'agreement_type' => (string) ($row['agreement_type'] ?? ''),
                'agreement_location' => (string) ($row['agreement_location'] ?? ''),
            ];
        }

        abort($response->status() ?: 500, $this->extractDotNetError($response));
    }

    private function fetchCustomerSummary(int $tenant): array
    {
        $response = $this->postDotNetWithApiFallback(
            $this->dotNetHttpClient(),
            $this->dotNetBaseUrl(),
            'CustomerPortal/CustomerPortal/GetCustomerDetails?customerId=' . $tenant,
            []
        );

        if (!$response->successful()) {
            Log::warning('Customer details endpoint failed for agreement workspace', [
                'tenant' => $tenant,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [
                'id_customer_vendor' => $tenant,
                'company_name' => '',
                'contact_name' => '',
                'contact_email' => '',
                'city' => '',
                'country' => '',
            ];
        }

        $payload = $response->json();
        if (!is_array($payload)) {
            return [
                'id_customer_vendor' => $tenant,
                'company_name' => '',
                'contact_name' => '',
                'contact_email' => '',
                'city' => '',
                'country' => '',
            ];
        }

        $customer = (array) ($payload['customer'] ?? $payload);

        $contacts = is_array($payload['contacts'] ?? null) ? $payload['contacts'] : [];
        $firstContact = is_array($contacts[0] ?? null) ? $contacts[0] : [];

        return [
            'id_customer_vendor' => (int) ($customer['id_customer_vendor'] ?? $customer['ID_CUSTOMER_VENDOR'] ?? $tenant),
            'company_name' => (string) ($customer['company_name'] ?? ''),
            'contact_name' => (string) ($firstContact['contact_name'] ?? $customer['contact_name'] ?? ''),
            'contact_email' => (string) ($firstContact['contact_email'] ?? $customer['contact_email'] ?? ''),
            'city' => (string) ($customer['city'] ?? ''),
            'country' => (string) ($customer['country'] ?? ''),
        ];
    }

    private function buildMergeFields(array $customer): array
    {
        $customerName = trim((string) ($customer['contact_name'] ?? ''));
        if ($customerName === '') {
            $customerName = trim((string) ($customer['company_name'] ?? 'Customer'));
        }

        return [
            'customer_id' => (string) ($customer['id_customer_vendor'] ?? ''),
            'customer_name' => $customerName,
            'company_name' => (string) ($customer['company_name'] ?? ''),
            'contact_name' => (string) ($customer['contact_name'] ?? ''),
            'contact_email' => (string) ($customer['contact_email'] ?? ''),
            'city' => (string) ($customer['city'] ?? ''),
            'country' => (string) ($customer['country'] ?? ''),
            'current_date' => Carbon::now()->format('F d, Y'),
            'current_year' => Carbon::now()->format('Y'),
        ];
    }

    private function mergeContent(string $content, array $mergeFields): string
    {
        if (trim($content) === '') {
            $content = '<p>Agreement content is empty. Please compose the agreement text before saving draft.</p>';
        }

        foreach ($mergeFields as $field => $value) {
            $token = '$' . $field;
            $content = str_replace($token, (string) $value, $content);
        }

        return $content;
    }

    private function extractAgreementContent(array $detail): string
    {
        $rawContent = (string) ($detail['document_content'] ?? $detail['agreement_content'] ?? $detail['content'] ?? $detail['html_content'] ?? '');
        if ($rawContent !== '') {
            return $rawContent;
        }

        $location = (string) ($detail['agreement_location'] ?? $detail['document_location'] ?? '');
        if ($location === '') {
            return '';
        }

        if ((bool) preg_match('/\.(doc|docx)$/i', $location)) {
            return $this->readDocumentContentFromLocation($location);
        }

        return $location;
    }

    private function readDocumentContentFromLocation(string $location): string
    {
        $absolutePath = $this->resolveDocumentLocationPath($location);
        if ($absolutePath === null || !is_file($absolutePath)) {
            return '';
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($extension !== 'docx') {
            return '';
        }

        return $this->extractHtmlFromDocx($absolutePath);
    }

    private function resolveDocumentLocationPath(string $location): ?string
    {
        $path = trim(str_replace('\\', '/', $location));
        if ($path === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        $candidates = [
            base_path($path),
            storage_path($path),
        ];

        $dotNetRoot = rtrim((string) env('DOTNET_PROJECT_ROOT', 'D:/Kallol/myProjects/dotNet/WrkPlan-US-Blazor/WrkPlanWebAPI'), '/');
        if ($dotNetRoot !== '') {
            $candidates[] = $dotNetRoot . '/' . $path;
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return $candidates[0] ?? null;
    }

    private function extractHtmlFromDocx(string $absolutePath): string
    {
        if (!class_exists('ZipArchive')) {
            return '';
        }

        $zip = new \ZipArchive();
        if ($zip->open($absolutePath) !== true) {
            return '';
        }

        $xml = $zip->getFromName('word/document.xml');
        $zip->close();

        if (!is_string($xml) || trim($xml) === '') {
            return '';
        }

        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new \DOMXPath($dom);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $bodyNode = $xpath->query('//w:body')->item(0);
        if (!$bodyNode instanceof \DOMElement) {
            return '';
        }

        $html = [];
        foreach ($bodyNode->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            if ($childNode->localName === 'p') {
                $html[] = $this->convertWordParagraphToHtml($xpath, $childNode);
            }
        }

        return implode('', $html);
    }

    private function convertWordParagraphToHtml(\DOMXPath $xpath, \DOMElement $paragraphNode): string
    {
        $tagName = 'p';
        $classNames = [];

        $styleValue = strtolower(trim((string) $xpath->evaluate('string(./w:pPr/w:pStyle/@w:val)', $paragraphNode)));
        if (preg_match('/heading\s*([1-6])|heading([1-6])/', $styleValue, $matches)) {
            $level = (int) ($matches[1] ?: $matches[2]);
            $tagName = 'h' . max(1, min(6, $level));
        }

        $alignment = strtolower(trim((string) $xpath->evaluate('string(./w:pPr/w:jc/@w:val)', $paragraphNode)));
        if (in_array($alignment, ['center', 'right', 'both'], true)) {
            $classNames[] = match ($alignment) {
                'center' => 'ql-align-center',
                'right' => 'ql-align-right',
                default => 'ql-align-justify',
            };
        }

        $innerHtml = $this->convertWordRunsToHtml($xpath, $paragraphNode);
        if ($innerHtml === '') {
            $innerHtml = '<br>';
        }

        $classAttr = $classNames === [] ? '' : ' class="' . implode(' ', $classNames) . '"';

        return '<' . $tagName . $classAttr . '>' . $innerHtml . '</' . $tagName . '>';
    }

    private function convertWordRunsToHtml(\DOMXPath $xpath, \DOMElement $paragraphNode): string
    {
        $parts = [];
        foreach ($paragraphNode->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            if ($childNode->localName === 'r') {
                $parts[] = $this->convertWordRunToHtml($xpath, $childNode);
                continue;
            }

            if ($childNode->localName === 'hyperlink') {
                foreach ($xpath->query('./w:r', $childNode) as $runNode) {
                    if ($runNode instanceof \DOMElement) {
                        $parts[] = $this->convertWordRunToHtml($xpath, $runNode);
                    }
                }
            }
        }

        return implode('', $parts);
    }

    private function convertWordRunToHtml(\DOMXPath $xpath, \DOMElement $runNode): string
    {
        $content = '';
        foreach ($runNode->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            if ($childNode->localName === 't') {
                $content .= e($childNode->textContent);
            } elseif ($childNode->localName === 'tab') {
                $content .= '&emsp;';
            } elseif ($childNode->localName === 'br') {
                $content .= '<br>';
            }
        }

        if ($content === '') {
            return '';
        }

        $isBold = (string) $xpath->evaluate('string(./w:rPr/w:b/@w:val)', $runNode) !== '0' && $xpath->query('./w:rPr/w:b', $runNode)->length > 0;
        $isItalic = (string) $xpath->evaluate('string(./w:rPr/w:i/@w:val)', $runNode) !== '0' && $xpath->query('./w:rPr/w:i', $runNode)->length > 0;
        $isUnderline = strtolower((string) $xpath->evaluate('string(./w:rPr/w:u/@w:val)', $runNode));
        $isUnderline = $isUnderline !== '' && $isUnderline !== 'none';

        $fontName = trim((string) $xpath->evaluate('string(./w:rPr/w:rFonts/@w:ascii)', $runNode));
        $fontSizeHalfPoint = trim((string) $xpath->evaluate('string(./w:rPr/w:sz/@w:val)', $runNode));
        $fontColor = strtoupper(trim((string) $xpath->evaluate('string(./w:rPr/w:color/@w:val)', $runNode)));

        $styles = [];
        if ($fontName !== '') {
            $styles[] = 'font-family:' . e($fontName);
        }

        $fontPx = $this->parseHalfPointSizeToPx($fontSizeHalfPoint);
        if ($fontPx !== null) {
            $styles[] = 'font-size:' . $fontPx . 'px';
        }

        $fontCssColor = $this->parseWordColorToCss($fontColor);
        if ($fontCssColor !== null) {
            $styles[] = 'color:' . $fontCssColor;
        }

        if ($styles !== []) {
            $content = '<span style="' . implode(';', $styles) . '">' . $content . '</span>';
        }

        if ($isUnderline) {
            $content = '<u>' . $content . '</u>';
        }
        if ($isItalic) {
            $content = '<em>' . $content . '</em>';
        }
        if ($isBold) {
            $content = '<strong>' . $content . '</strong>';
        }

        return $content;
    }

    private function parseHalfPointSizeToPx(string $halfPointValue): ?int
    {
        if ($halfPointValue === '' || !ctype_digit($halfPointValue)) {
            return null;
        }

        $halfPoint = (int) $halfPointValue;
        if ($halfPoint <= 0) {
            return null;
        }

        return (int) round($halfPoint * 0.6667);
    }

    private function parseWordColorToCss(string $wordColor): ?string
    {
        if ($wordColor === '' || in_array($wordColor, ['AUTO', 'FFFFFF00'], true)) {
            return null;
        }

        if (preg_match('/^[0-9A-F]{6}$/', $wordColor) === 1) {
            return '#' . $wordColor;
        }

        return null;
    }

    private function resolveAbsoluteRecordPath(string $location): ?string
    {
        $path = trim(str_replace('\\', '/', $location));
        if ($path === '') {
            return null;
        }

        if (preg_match('/^[A-Za-z]:\//', $path) === 1 || str_starts_with($path, '/')) {
            return $path;
        }

        return storage_path($path);
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
            ->timeout(25);

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

        return 'Remote API request failed with status ' . $response->status() . '.';
    }
}
