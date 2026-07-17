<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            'document_title' => 'nullable|string|max:255',
            'document_content' => 'required|string',
        ]);

        $documentContent = (string) $request->input('document_content', '');

        $payload = [
            'document_type' => (string) $request->input('document_type'),
            'document_location' => $documentContent,
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
        $agreement = $this->fetchAgreementDetailOrAbort($contract);

        return view('admin.contracts.show', compact('agreement'));
    }

    public function downloadWord(int $contract)
    {
        $agreement = $this->fetchAgreementDetailOrAbort($contract);

        if ($this->looksLikeDocumentPath((string) ($agreement->document_location ?? ''))) {
            $absolutePath = $this->resolveDocumentLocationPath((string) $agreement->document_location);
            if ($absolutePath !== null && is_file($absolutePath)) {
                return response()->download($absolutePath, basename($absolutePath));
            }
        }

        $title = trim((string) ($agreement->document_title ?: ('agreement_' . $agreement->id)));
        $safeTitle = Str::of($title)->ascii()->replaceMatches('/[^A-Za-z0-9\-_]+/', '_')->trim('_')->toString();
        if ($safeTitle === '') {
            $safeTitle = 'agreement_' . $agreement->id;
        }

        $bodyHtml = (string) ($agreement->document_content ?: '<p></p>');
        $document = '<html><head><meta charset="UTF-8"></head><body>'
            . '<div>' . $bodyHtml . '</div>'
            . '</body></html>';

        return response($document, 200, [
            'Content-Type' => 'application/msword; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $safeTitle . '.doc"',
        ]);
    }

    public function update(Request $request, int $contract)
    {
        $existing = $this->fetchAgreementDetailOrAbort($contract);

        $request->validate([
            'document_type' => 'required|string|max:255',
            'document_title' => 'nullable|string|max:255',
            'document_content' => 'required|string',
        ]);

        $documentContent = (string) $request->input('document_content', '');
        $documentLocation = (string) ($existing->document_location ?? '');

        if ($this->looksLikeDocumentPath($documentLocation)) {
            try {
                $this->saveDocumentContentToLocation($documentLocation, $documentContent);
            } catch (\RuntimeException $exception) {
                return back()
                    ->withErrors(['api' => $exception->getMessage()])
                    ->withInput();
            }
        }

        $payload = [
            'document_type' => (string) $request->input('document_type'),
            'document_location' => $this->looksLikeDocumentPath($documentLocation)
                ? $documentLocation
                : $documentContent,
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
        $documentType = (string) ($row['document_type'] ?? '');

        return (object) [
            'id' => (int) ($row['iD_CONTRACT_AGREEMENT'] ?? $row['ID_CONTRACT_AGREEMENT'] ?? 0),
            'id_company' => (int) ($row['iD_COMPANY'] ?? $row['ID_COMPANY'] ?? 0),
            'document_type' => $documentType,
            'document_title' => (string) ($row['document_title'] ?? $documentType),
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

        $documentLocation = (string) ($row['document_location'] ?? '');
        $rawContent = (string) ($row['document_content'] ?? '');

        if ($rawContent === '' && $this->looksLikeDocumentPath($documentLocation)) {
            $rawContent = $this->readDocumentContentFromLocation($documentLocation);
        }

        if ($rawContent === '' && !$this->looksLikeDocumentPath($documentLocation)) {
            $rawContent = $documentLocation;
        }

        $agreement->document_content = $this->normalizeDocumentContent($rawContent);
        $agreement->document_location = $documentLocation;
        $agreement->content_length = mb_strlen(trim(strip_tags($agreement->document_content)));

        return $agreement;
    }

    private function normalizeDocumentContent(string $raw): string
    {
        $value = trim($raw);
        if ($value === '') {
            return '';
        }

        // Some API responses can return encoded HTML from legacy storage.
        $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($this->looksLikeHtml($decoded)) {
            return $decoded;
        }

        return nl2br(e($decoded));
    }

    private function looksLikeHtml(string $value): bool
    {
        return (bool) preg_match('/<\/?[a-z][^>]*>/i', $value);
    }

    private function looksLikeDocumentPath(string $value): bool
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return false;
        }

        return (bool) preg_match('/\.(docx|doc)$/i', $trimmed);
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

    private function saveDocumentContentToLocation(string $location, string $htmlContent): void
    {
        $absolutePath = $this->resolveDocumentLocationPath($location);
        if ($absolutePath === null || !is_file($absolutePath)) {
            throw new \RuntimeException('The source document could not be found at the configured location.');
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if ($extension !== 'docx') {
            throw new \RuntimeException('Only DOCX-backed agreements can be updated in place.');
        }

        $this->writeHtmlToDocx($absolutePath, $htmlContent);
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

        $body = $xpath->query('//w:body')->item(0);
        if (!$body instanceof \DOMElement) {
            return '';
        }

        $html = [];
        foreach ($body->childNodes as $childNode) {
            if (!$childNode instanceof \DOMElement) {
                continue;
            }

            if ($childNode->localName === 'p') {
                $html[] = $this->convertWordParagraphToHtml($xpath, $childNode);
            }
        }

        return implode('', $html);
    }

    private function writeHtmlToDocx(string $absolutePath, string $htmlContent): void
    {
        if (!class_exists('ZipArchive')) {
            throw new \RuntimeException('ZipArchive is not available on this server.');
        }

        $bodyXml = $this->convertHtmlToWordBodyXml($htmlContent);

        $documentXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas"'
            . ' xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006"'
            . ' xmlns:o="urn:schemas-microsoft-com:office:office"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"'
            . ' xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math"'
            . ' xmlns:v="urn:schemas-microsoft-com:vml"'
            . ' xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing"'
            . ' xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"'
            . ' xmlns:w10="urn:schemas-microsoft-com:office:word"'
            . ' xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"'
            . ' xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml"'
            . ' xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml"'
            . ' xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup"'
            . ' xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk"'
            . ' xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml"'
            . ' xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape"'
            . ' mc:Ignorable="w14 w15 wp14">'
            . '<w:body>' . $bodyXml
            . '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/>'
            . '<w:pgMar w:top="1440" w:right="1440" w:bottom="1440" w:left="1440" w:header="708" w:footer="708" w:gutter="0"/>'
            . '<w:cols w:space="708"/><w:docGrid w:linePitch="360"/></w:sectPr>'
            . '</w:body></w:document>';

        $temporaryPath = $this->createTemporaryDocxCopy($absolutePath);

        $zip = new \ZipArchive();
        if ($zip->open($temporaryPath) !== true) {
            @unlink($temporaryPath);
            throw new \RuntimeException('Unable to open a temporary copy of the Word document for saving.');
        }

        if ($zip->addFromString('word/document.xml', $documentXml) === false) {
            $zip->close();
            @unlink($temporaryPath);
            throw new \RuntimeException('Unable to write the updated document content into the Word file.');
        }

        if ($zip->close() === false) {
            @unlink($temporaryPath);
            throw new \RuntimeException('Failed to finalize the updated Word document.');
        }

        $copied = @copy($temporaryPath, $absolutePath);
        @unlink($temporaryPath);

        if (!$copied) {
            throw new \RuntimeException('Unable to overwrite the original Word document. It may be open in Microsoft Word or blocked by file permissions.');
        }
    }

    private function createTemporaryDocxCopy(string $absolutePath): string
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'wrkplan_docx_');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Unable to create a temporary file for document editing.');
        }

        $temporaryDocxPath = $temporaryPath . '.docx';

        if (!@copy($absolutePath, $temporaryDocxPath)) {
            @unlink($temporaryPath);
            @unlink($temporaryDocxPath);
            throw new \RuntimeException('Unable to create a working copy of the original Word document.');
        }

        @unlink($temporaryPath);

        return $temporaryDocxPath;
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

    private function convertHtmlToWordBodyXml(string $htmlContent): string
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $wrappedHtml = '<div>' . $htmlContent . '</div>';
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $wrappedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

        $root = $dom->getElementsByTagName('div')->item(0);
        if (!$root instanceof \DOMElement) {
            return '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
        }

        $paragraphs = [];
        foreach ($root->childNodes as $childNode) {
            $this->appendHtmlNodeAsWordParagraphs($childNode, $paragraphs, []);
        }

        if ($paragraphs === []) {
            $paragraphs[] = '<w:p><w:r><w:t xml:space="preserve"></w:t></w:r></w:p>';
        }

        return implode('', $paragraphs);
    }

    private function appendHtmlNodeAsWordParagraphs(\DOMNode $node, array &$paragraphs, array $inheritedMarks): void
    {
        if ($node instanceof \DOMText) {
            $text = trim($node->textContent);
            if ($text !== '') {
                $paragraphs[] = $this->buildWordParagraphXml('<w:r><w:t xml:space="preserve">' . htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r>');
            }
            return;
        }

        if (!$node instanceof \DOMElement) {
            return;
        }

        $tag = strtolower($node->tagName);

        if (in_array($tag, ['p', 'div', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true)) {
            $runs = $this->convertHtmlChildrenToWordRuns($node, $inheritedMarks);
            $paragraphs[] = $this->buildWordParagraphXml($runs === '' ? '<w:r><w:t xml:space="preserve"></w:t></w:r>' : $runs, $tag, $node);
            return;
        }

        if (in_array($tag, ['ul', 'ol'], true)) {
            $index = 1;
            foreach ($node->childNodes as $childNode) {
                if ($childNode instanceof \DOMElement && strtolower($childNode->tagName) === 'li') {
                    $prefix = $tag === 'ol' ? $index . '. ' : '• ';
                    $runs = '<w:r><w:t xml:space="preserve">' . htmlspecialchars($prefix, ENT_XML1 | ENT_COMPAT, 'UTF-8') . '</w:t></w:r>'
                        . $this->convertHtmlChildrenToWordRuns($childNode, $inheritedMarks);
                    $paragraphs[] = $this->buildWordParagraphXml($runs, 'li', $childNode);
                    $index++;
                }
            }
            return;
        }

        $runs = $this->convertHtmlChildrenToWordRuns($node, $inheritedMarks);
        if ($runs !== '') {
            $paragraphs[] = $this->buildWordParagraphXml($runs);
        }
    }

    private function convertHtmlChildrenToWordRuns(\DOMNode $parentNode, array $marks): string
    {
        $runs = '';
        foreach ($parentNode->childNodes as $childNode) {
            $runs .= $this->convertHtmlNodeToWordRuns($childNode, $marks);
        }

        return $runs;
    }

    private function convertHtmlNodeToWordRuns(\DOMNode $node, array $marks): string
    {
        if ($node instanceof \DOMText) {
            $text = $node->textContent;
            if ($text === '') {
                return '';
            }

            return $this->buildWordRunXml($text, $marks);
        }

        if (!$node instanceof \DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if ($tag === 'br') {
            return '<w:r><w:br/></w:r>';
        }

        $nextMarks = $marks;
        if (in_array($tag, ['strong', 'b'], true)) {
            $nextMarks['bold'] = true;
        }
        if (in_array($tag, ['em', 'i'], true)) {
            $nextMarks['italic'] = true;
        }
        if ($tag === 'u') {
            $nextMarks['underline'] = true;
        }

        return $this->convertHtmlChildrenToWordRuns($node, $nextMarks);
    }

    private function buildWordRunXml(string $text, array $marks): string
    {
        if ($text === '') {
            return '';
        }

        $safeText = htmlspecialchars($text, ENT_XML1 | ENT_COMPAT, 'UTF-8');
        $properties = '';
        if (!empty($marks['bold'])) {
            $properties .= '<w:b/>';
        }
        if (!empty($marks['italic'])) {
            $properties .= '<w:i/>';
        }
        if (!empty($marks['underline'])) {
            $properties .= '<w:u w:val="single"/>';
        }

        $runProperties = $properties === '' ? '' : '<w:rPr>' . $properties . '</w:rPr>';

        return '<w:r>' . $runProperties . '<w:t xml:space="preserve">' . $safeText . '</w:t></w:r>';
    }

    private function buildWordParagraphXml(string $runsXml, string $tag = 'p', ?\DOMElement $sourceNode = null): string
    {
        $paragraphProperties = '';

        if (preg_match('/^h([1-6])$/', $tag, $matches)) {
            $paragraphProperties .= '<w:pStyle w:val="Heading' . $matches[1] . '"/>';
        }

        if ($sourceNode instanceof \DOMElement) {
            $className = ' ' . strtolower((string) $sourceNode->getAttribute('class')) . ' ';
            if (str_contains($className, ' ql-align-center ')) {
                $paragraphProperties .= '<w:jc w:val="center"/>';
            } elseif (str_contains($className, ' ql-align-right ')) {
                $paragraphProperties .= '<w:jc w:val="right"/>';
            } elseif (str_contains($className, ' ql-align-justify ')) {
                $paragraphProperties .= '<w:jc w:val="both"/>';
            }
        }

        $pPr = $paragraphProperties === '' ? '' : '<w:pPr>' . $paragraphProperties . '</w:pPr>';

        return '<w:p>' . $pPr . $runsXml . '</w:p>';
    }

    private function fetchAgreementDetailOrAbort(int $contract): object
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

        return $this->mapAgreementDetail($payload);
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
