<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\AgreementDocxBuilder;
use App\Support\CustomerAgreementStore;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class CustomerTaggedAgreementController extends Controller
{
    public function __construct(
        private readonly CustomerAgreementStore $agreementStore,
        private readonly AgreementDocxBuilder $docxBuilder,
    ) {}

    public function show(string $recordId)
    {
        $customerId = (int) auth()->user()->tenant_id;
        $record = $this->agreementStore->findRecord($customerId, $recordId);

        if ($record === null || !in_array((string) ($record['status'] ?? ''), ['sent', 'acknowledged'], true)) {
            abort(404);
        }

        return view('customer.contracts.tagged-show', [
            'record' => (object) $record,
        ]);
    }

    public function agree(Request $request, string $recordId): RedirectResponse
    {
        $request->validate([
            'signer_name' => 'required|string|max:120',
            'signature_image' => 'required|string',
        ]);

        $customerId = (int) auth()->user()->tenant_id;
        $record = $this->agreementStore->findRecord($customerId, $recordId);
        if ($record === null || !in_array((string) ($record['status'] ?? ''), ['sent', 'acknowledged'], true)) {
            return back()->withErrors(['contract' => 'Agreement record not found.']);
        }

        $decoded = $this->decodeSignatureDataUrl((string) $request->input('signature_image'));
        if ($decoded === null) {
            return back()->withErrors(['signature' => 'Signature is invalid. Please sign again.']);
        }

        $signerName = (string) $request->input('signer_name');
        $signatureData = (string) $request->input('signature_image');
        $signedAt = Carbon::now();

        $signaturePath = $this->writeSignatureArtifact($customerId, $recordId, $decoded);
        if ($signaturePath === null) {
            return back()->withErrors(['signature' => 'Unable to save signature artifact.']);
        }

        $taggedLocation = $this->normalizeTaggedLocation((string) ($record['tagged_agreement_location'] ?? ''), $customerId, $recordId);
        if ($taggedLocation === null) {
            return back()->withErrors(['signature' => 'Unable to resolve the tagged agreement document path.']);
        }

        $docxSaved = $this->writeSignedDocx(
            $taggedLocation['absolute'],
            (string) ($record['content'] ?? ''),
            $decoded,
            $signerName,
            $signedAt
        );

        if (!$docxSaved) {
            return back()->withErrors(['signature' => 'Failed to embed signature into tagged agreement DOCX.']);
        }

        $updated = $this->agreementStore->updateRecord($customerId, $recordId, function (array $record) use ($customerId, $recordId, $signerName, $signatureData, $signedAt, $taggedLocation): array {
            if (!in_array((string) ($record['status'] ?? ''), ['sent', 'acknowledged'], true)) {
                return $record;
            }

            $record['status'] = 'acknowledged';
            $record['customer_acknowledged_at'] = $signedAt->toIso8601String();
            $record['signature_signed_at'] = $signedAt->toIso8601String();
            $record['signature_signer_name'] = $signerName;
            $record['signature_data'] = $signatureData;
            $record['signature_artifact_path'] = $this->relativeSignaturePath($customerId, $recordId);
            $record['tagged_agreement_location'] = $taggedLocation['relative'];

            return $record;
        });

        if ($updated === null) {
            return back()->withErrors(['contract' => 'Agreement record not found.']);
        }

        return back()->with('success', 'Agreement acknowledged and signature saved successfully.');
    }

    public function signature(string $recordId)
    {
        $customerId = (int) auth()->user()->tenant_id;
        $record = $this->agreementStore->findRecord($customerId, $recordId);
        if ($record === null) {
            abort(404);
        }

        $relative = (string) ($record['signature_artifact_path'] ?? '');
        if ($relative === '') {
            abort(404);
        }

        $absolute = storage_path('app/' . ltrim($relative, '/'));
        if (!is_file($absolute)) {
            abort(404);
        }

        return Response::file($absolute, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline',
        ]);
    }

    private function decodeSignatureDataUrl(string $signatureData): ?string
    {
        if (!preg_match('/^data:image\/(png|jpeg);base64,/', $signatureData)) {
            return null;
        }

        $parts = explode(',', $signatureData, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $decoded = base64_decode($parts[1], true);
        if (!is_string($decoded) || $decoded === '') {
            return null;
        }

        if (strlen($decoded) > 2 * 1024 * 1024) {
            return null;
        }

        return $decoded;
    }

    private function relativeSignaturePath(int $customerId, string $recordId): string
    {
        return 'customer-agreements/' . $customerId . '/signatures/' . $recordId . '.png';
    }

    private function writeSignatureArtifact(int $customerId, string $recordId, string $decoded): ?string
    {
        $relative = $this->relativeSignaturePath($customerId, $recordId);
        $absolute = storage_path('app/' . $relative);
        $dir = dirname($absolute);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return null;
        }

        $written = @file_put_contents($absolute, $decoded);
        if ($written === false) {
            return null;
        }

        return $absolute;
    }

    private function normalizeTaggedLocation(string $location, int $customerId, string $recordId): ?array
    {
        $trimmed = trim(str_replace('\\', '/', $location));
        if ($trimmed === '') {
            $trimmed = 'app/public/' . $customerId . '/signed-' . $recordId . '.docx';
        }

        if (!preg_match('/\.docx$/i', $trimmed)) {
            $trimmed = preg_replace('/\.[A-Za-z0-9]+$/', '', $trimmed) ?: $trimmed;
            $trimmed .= '.docx';
        }

        $absolute = preg_match('/^[A-Za-z]:\//', $trimmed) === 1 || str_starts_with($trimmed, '/')
            ? $trimmed
            : storage_path($trimmed);

        if (!is_string($absolute) || trim($absolute) === '') {
            return null;
        }

        return [
            'relative' => $trimmed,
            'absolute' => $absolute,
        ];
    }

    private function writeSignedDocx(string $absoluteDocxPath, string $htmlContent, string $signatureBytes, string $signerName, Carbon $signedAt): bool
    {
        $dir = dirname($absoluteDocxPath);
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            return false;
        }

        if (trim($htmlContent) === '') {
            $htmlContent = '<p>Agreement acknowledged by customer.</p>';
        }

        try {
            $docxBytes = $this->docxBuilder->buildFromHtmlWithSignature($htmlContent, $signatureBytes, $signerName, $signedAt);
        } catch (\RuntimeException) {
            return false;
        }

        return @file_put_contents($absoluteDocxPath, $docxBytes) !== false;
    }
}
