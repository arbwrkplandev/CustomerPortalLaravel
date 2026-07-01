<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Tenant;
use App\Services\Admin\ContractService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminContractController extends Controller
{
    public function __construct(protected ContractService $contractService) {}

    public function index(Request $request)
    {
        $contracts = $this->contractService->list($request->only(['search', 'tenant_id', 'status']));
        $tenants   = Tenant::select('id', 'company_name')->orderBy('company_name')->get();
        return view('admin.contracts.index', compact('contracts', 'tenants'));
    }

    public function create()
    {
        $tenants = Tenant::where('status', 'active')->orderBy('company_name')->get();
        return view('admin.contracts.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tenant_id'   => 'required|exists:tenants,id',
            'title'       => 'required|string|max:255',
            'type'        => 'nullable|in:service,nda,sla,custom',
            'html_content'=> 'nullable|string',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:20480',
            'sign_fields' => 'nullable|array',
        ]);

        $data = $request->except('pdf_file');
        if ($request->hasFile('pdf_file')) {
            $data['pdf_file'] = $request->file('pdf_file');
        }

        $contract = $this->contractService->create($data);
        return redirect()->route('admin.contracts.show', $contract)
            ->with('success', 'Contract created successfully.');
    }

    public function show(Contract $contract)
    {
        $contract->load(['tenant', 'signFields', 'files']);
        return view('admin.contracts.show', compact('contract'));
    }

    public function send(Contract $contract)
    {
        $this->contractService->sendToCustomer($contract);
        return back()->with('success', 'Contract sent to customer for signature.');
    }

    public function streamPdf(Contract $contract, string $type = 'original')
    {
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
        $path = $type === 'signed' ? $contract->signed_pdf_path : $contract->original_pdf_path;
        if (!$path || !Storage::exists($path)) {
            return back()->with('error', 'PDF not available.');
        }
        return Storage::download($path, $type . '_' . $contract->contract_number . '.pdf');
    }
}
