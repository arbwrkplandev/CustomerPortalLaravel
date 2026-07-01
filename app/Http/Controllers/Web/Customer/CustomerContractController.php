<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Services\Admin\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerContractController extends Controller
{
    public function __construct(protected ContractService $contractService) {}

    public function index()
    {
        $contracts = Contract::where('tenant_id', Auth::user()->tenant_id)
            ->with(['files'])->latest()->paginate(10);
        return view('customer.contracts.index', compact('contracts'));
    }

    public function show(Contract $contract)
    {
        abort_unless($contract->tenant_id === Auth::user()->tenant_id, 403);
        $contract->load(['signFields', 'files', 'tenant']);
        return view('customer.contracts.show', compact('contract'));
    }

    public function sign(Request $request, Contract $contract)
    {
        abort_unless($contract->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless(
            in_array($contract->status, ['sent', 'pending_signature']),
            422,
            'This contract is not available for signing.'
        );

        $request->validate([
            'signature_image' => 'required|string',
            'signer_name'     => 'required|string|max:255',
        ]);

        // Build field values array
        $fields = [];
        foreach ($contract->signFields as $field) {
            if (in_array($field->field_type, ['signature', 'initials'])) {
                $fields[$field->id] = $request->input('signature_image');
            } elseif ($request->has('field_' . $field->id)) {
                $fields[$field->id] = $request->input('field_' . $field->id);
            }
        }

        $this->contractService->signContract($contract, [
            'signature_image' => $request->signature_image,
            'signer_name'     => $request->signer_name,
            'fields'          => $fields,
        ]);

        return redirect()->route('customer.contracts.show', $contract)
            ->with('signed', true);
    }

    public function uploadSigned(Request $request, Contract $contract)
    {
        abort_unless($contract->tenant_id === Auth::user()->tenant_id, 403);
        $request->validate(['signed_file' => 'required|file|mimes:pdf|max:20480']);
        $this->contractService->uploadSignedCopy($contract, $request->file('signed_file'));
        return redirect()->route('customer.contracts.show', $contract)
            ->with('success', 'Signed contract uploaded successfully!');
    }

    public function streamPdf(Contract $contract, string $type = 'original')
    {
        abort_unless($contract->tenant_id === Auth::user()->tenant_id, 403);
        $path = $type === 'signed' ? $contract->signed_pdf_path : $contract->original_pdf_path;
        if (!$path || !Storage::exists($path)) {
            abort(404, 'PDF not available');
        }
        return response()->file(
            Storage::path($path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }

    public function download(Contract $contract, string $type = 'original')
    {
        abort_unless($contract->tenant_id === Auth::user()->tenant_id, 403);
        $path = $type === 'signed' ? $contract->signed_pdf_path : $contract->original_pdf_path;
        if (!$path || !Storage::exists($path)) {
            return back()->with('error', 'PDF not available.');
        }
        return Storage::download($path, $type . '_' . $contract->contract_number . '.pdf');
    }
}
