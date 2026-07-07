<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\ContractService;
use App\Models\Contract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Admin - Contracts", description="Contract management and e-sign")
 */
class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(protected ContractService $contractService) {}

    public function index(Request $request): JsonResponse
    {
        $contracts = $this->contractService->list(
            $request->only(['search', 'tenant_id', 'status', 'date_from', 'date_to']),
            $request->integer('per_page', 15)
        );
        return $this->paginated($contracts);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'tenant_id'    => 'required|exists:tenants,id',
            'title'        => 'required|string|max:255',
            'type'         => 'nullable|in:service,nda,sla,custom',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date|after_or_equal:start_date',
            'signer_email' => 'nullable|email',
            'html_content' => 'nullable|string',
            'pdf_file'     => 'nullable|file|mimes:pdf|max:10240',
            'sign_fields'  => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $data = $request->all();
        if ($request->hasFile('pdf_file')) {
            $data['pdf_file'] = $request->file('pdf_file');
        }

        $contract = $this->contractService->create($data);
        return $this->created($contract);
    }

    public function show(Contract $contract): JsonResponse
    {
        return $this->success($contract->load(['tenant', 'signFields', 'files']));
    }

    public function sendToCustomer(Contract $contract): JsonResponse
    {
        $contract = $this->contractService->sendToCustomer($contract);
        return $this->success($contract, 'Contract sent to customer');
    }

    public function revokeFromCustomer(Contract $contract): JsonResponse
    {
        try {
            $contract = $this->contractService->revokeFromCustomer($contract);
            return $this->success($contract, 'Contract revoked — status reset to Draft');
        } catch (\RuntimeException $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function download(Contract $contract, string $type = 'original'): mixed
    {
        $path = $this->resolvePdfPath($contract, $type);
        if (!$path || !Storage::exists($path)) {
            return $this->notFound('PDF not found');
        }
        return Storage::download($path);
    }

    public function stream(Contract $contract, string $type = 'original'): mixed
    {
        $path = $this->resolvePdfPath($contract, $type);
        if (!$path || !Storage::exists($path)) {
            return $this->notFound('PDF not found');
        }

        return response()->file(
            Storage::path($path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }

    protected function resolvePdfPath(Contract $contract, string $type): ?string
    {
        if ($type === 'signed') {
            return $contract->signed_pdf_path;
        }

        return $contract->original_pdf_path ?: $contract->signed_pdf_path;
    }
}
