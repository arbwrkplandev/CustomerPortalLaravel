<?php

namespace App\Services\Admin;

use App\Models\Contract;
use App\Models\ContractFile;
use App\Models\ContractSignField;
use App\Models\Tenant;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = Contract::with(['tenant']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%$s%")
                  ->orWhere('contract_number', 'like', "%$s%");
            });
        }
        if (!empty($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    public function create(array $data): Contract
    {
        $contract = Contract::create([
            'tenant_id'       => $data['tenant_id'],
            'contract_number' => 'CTR-' . strtoupper(Str::random(8)),
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'type'            => $data['type'] ?? 'service',
            'status'          => 'draft',
            'start_date'      => $data['start_date'] ?? null,
            'end_date'        => $data['end_date'] ?? null,
            'html_content'    => $data['html_content'] ?? $data['content'] ?? null,
            'signer_email'    => $data['signer_email'] ?? null,
            'created_by'      => auth()->id(),
        ]);

        // Add sign fields if provided
        if (!empty($data['sign_fields'])) {
            foreach ($data['sign_fields'] as $field) {
                ContractSignField::create([
                    'contract_id' => $contract->id,
                    'field_type'  => $field['field_type'] ?? 'signature',
                    'label'       => $field['label'],
                    'page_number' => $field['page_number'] ?? 1,
                    'x_position'  => $field['x_position'] ?? 0,
                    'y_position'  => $field['y_position'] ?? 0,
                    'width'       => $field['width'] ?? 200,
                    'height'      => $field['height'] ?? 60,
                    'required'    => $field['required'] ?? true,
                ]);
            }
        }

        // Handle uploaded PDF
        if (!empty($data['pdf_file'])) {
            $path = $data['pdf_file']->store('contracts/originals', 'local');
            $contract->update(['original_pdf_path' => $path]);
            ContractFile::create([
                'contract_id' => $contract->id,
                'tenant_id'   => $data['tenant_id'],
                'file_type'   => 'original',
                'file_path'   => $path,
                'file_name'   => $data['pdf_file']->getClientOriginalName(),
                'mime_type'   => $data['pdf_file']->getMimeType(),
                'file_size'   => $data['pdf_file']->getSize(),
                'uploaded_by' => auth()->id(),
            ]);
        }

        return $contract->load(['tenant', 'signFields', 'files']);
    }

    public function sendToCustomer(Contract $contract): Contract
    {
        $contract->update(['status' => 'pending_signature', 'sent_at' => now()]);
        return $contract->fresh();
    }

    public function signContract(Contract $contract, array $signatureData): Contract
    {
        // Persist the signature image as a PNG file so it survives forever
        if (!empty($signatureData['signature_image']) && str_starts_with($signatureData['signature_image'], 'data:image')) {
            $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData['signature_image']);
            $imgPath = 'contracts/signatures/' . $contract->contract_number . '_sig.png';
            Storage::put($imgPath, base64_decode($base64));
            $signatureData['signature_path'] = $imgPath;
        }

        // Update sign field values
        foreach ($signatureData['fields'] ?? [] as $fieldId => $value) {
            ContractSignField::where('contract_id', $contract->id)
                ->where('id', $fieldId)
                ->update(['value' => $value]);
        }

        // Generate signed PDF
        $signedPath = $this->generateSignedPdf($contract, $signatureData);

        $contract->update([
            'status'          => 'signed',
            'signed_at'       => now(),
            'signer_name'     => $signatureData['signer_name'] ?? null,
            'signer_ip'       => request()->ip(),
            'signed_pdf_path' => $signedPath,
        ]);

        // Store signed file record
        ContractFile::create([
            'contract_id' => $contract->id,
            'tenant_id'   => $contract->tenant_id,
            'file_type'   => 'signed',
            'file_path'   => $signedPath,
            'file_name'   => 'signed_' . $contract->contract_number . '.pdf',
            'mime_type'   => 'application/pdf',
            'uploaded_by' => auth()->id() ?? 0,
        ]);

        return $contract->fresh(['tenant', 'signFields', 'files']);
    }

    public function uploadSignedCopy(Contract $contract, $file): Contract
    {
        $path = $file->store('contracts/signed', 'local');

        $contract->update([
            'status'          => 'signed',
            'signed_at'       => now(),
            'signed_pdf_path' => $path,
            'signer_ip'       => request()->ip(),
        ]);

        ContractFile::create([
            'contract_id' => $contract->id,
            'tenant_id'   => $contract->tenant_id,
            'file_type'   => 'signed',
            'file_path'   => $path,
            'file_name'   => $file->getClientOriginalName(),
            'mime_type'   => $file->getMimeType(),
            'file_size'   => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return $contract->fresh();
    }

    protected function generateSignedPdf(Contract $contract, array $signatureData): string
    {
        $contract->load(['tenant', 'signFields']);

        // Set status to 'signed' on the model instance so the PDF template
        // renders correctly — this does NOT persist to the database here.
        $contract->status = 'signed';

        // Prefer the saved PNG file path for DomPDF (more reliable than data URL)
        if (!empty($signatureData['signature_path']) && Storage::exists($signatureData['signature_path'])) {
            $signatureData['signature_image'] = Storage::path($signatureData['signature_path']);
        }

        $pdf = Pdf::loadView('pdf.contract-signed-full', [
            'contract'      => $contract,
            'signatureData' => $signatureData,
            'signedAt'      => now()->format('Y-m-d H:i:s T'),
        ])->setPaper('a4');

        $path = 'contracts/signed/' . $contract->contract_number . '_signed_' . now()->format('YmdHis') . '.pdf';
        Storage::put($path, $pdf->output());
        return $path;
    }
}
