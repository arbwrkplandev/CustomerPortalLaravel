<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\Contract;
use App\Services\Admin\ContractService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Customer - Contracts", description="Customer contract view and signing")
 */
class ContractController extends Controller
{
    use ApiResponse;

    public function __construct(protected ContractService $contractService) {}

    public function index(Request $request): JsonResponse
    {
        $tenantId = Auth::user()->tenant_id;
        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['files', 'signFields'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($contracts);
    }

    public function show(int $id): JsonResponse
    {
        $contract = Contract::where('tenant_id', Auth::user()->tenant_id)
            ->with(['signFields', 'files'])
            ->findOrFail($id);

        return $this->success($contract);
    }

    /**
     * E-sign: Customer signs the contract in-app.
     */
    public function sign(Request $request, int $id): JsonResponse
    {
        $contract = Contract::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('status', ['sent', 'pending_signature'])
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'signer_name'      => 'required|string|max:255',
            'signature_data'   => 'required|string', // Base64 signature image
            'fields'           => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $signatureData = array_merge($request->all(), [
            'signer_name' => $request->signer_name,
        ]);

        $contract = $this->contractService->signContract($contract, $signatureData);
        return $this->success($contract, 'Contract signed successfully');
    }

    /**
     * Upload a manually signed PDF copy.
     */
    public function uploadSigned(Request $request, int $id): JsonResponse
    {
        $contract = Contract::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'signed_pdf' => 'required|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $contract = $this->contractService->uploadSignedCopy($contract, $request->file('signed_pdf'));
        return $this->success($contract, 'Signed contract uploaded successfully');
    }

    public function download(int $id, string $type = 'original'): mixed
    {
        $contract = Contract::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $path = $type === 'signed' ? $contract->signed_pdf_path : $contract->original_pdf_path;

        if (!$path || !\Illuminate\Support\Facades\Storage::exists($path)) {
            return $this->notFound('PDF not found');
        }

        return \Illuminate\Support\Facades\Storage::download($path);
    }
}
