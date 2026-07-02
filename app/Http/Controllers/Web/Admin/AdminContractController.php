<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminContractController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $contractsResponse = $this->api->get('/admin/contracts', [
            'search' => $request->query('search'),
            'tenant_id' => $request->query('tenant_id'),
            'status' => $request->query('status'),
            'per_page' => 15,
            'page' => $request->integer('page', 1),
        ]);

        $tenantsResponse = $this->api->get('/admin/tenants', [
            'per_page' => 200,
            'status' => 'active',
        ]);

        $contracts = $this->api->toPaginator($contractsResponse, 15);
        $tenants = $this->api->toEntities($tenantsResponse['data'] ?? []);

        return view('admin.contracts.index', compact('contracts', 'tenants'));
    }

    public function create()
    {
        $tenantsResponse = $this->api->get('/admin/tenants', [
            'status' => 'active',
            'per_page' => 200,
        ]);
        $tenants = $this->api->toEntities($tenantsResponse['data'] ?? []);

        return view('admin.contracts.create', compact('tenants'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tenant_id'   => 'required|integer',
            'title'       => 'required|string|max:255',
            'type'        => 'nullable|in:service,nda,sla,custom',
            'html_content'=> 'nullable|string',
            'pdf_file'    => 'nullable|file|mimes:pdf|max:20480',
            'sign_fields' => 'nullable|array',
        ]);

        $data = $request->except('pdf_file');
        $files = $request->hasFile('pdf_file') ? ['pdf_file' => $request->file('pdf_file')] : [];

        $response = $this->api->postWithFiles('/admin/contracts', $data, $files);
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        $contract = $this->api->toEntities($response['data'] ?? []);

        return redirect()->route('admin.contracts.show', $contract->id)
            ->with('success', 'Contract created successfully.');
    }

    public function show(int $contract)
    {
        $response = $this->api->get('/admin/contracts/' . $contract);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $contract = $this->api->toEntities($response['data'] ?? []);

        return view('admin.contracts.show', compact('contract'));
    }

    public function send(Request $request, int $contract)
    {
        $response = $this->api->post('/admin/contracts/' . $contract . '/send');
        if (!($response['success'] ?? false)) {
            if ($request->wantsJson()) {
                return response()->json($response, 422);
            }
            return back()->withErrors($this->api->extractErrors($response));
        }
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Contract sent to customer for signature.']);
        }
        return back()->with('success', 'Contract sent to customer for signature.');
    }

    public function revoke(Request $request, int $contract)
    {
        $response = $this->api->post('/admin/contracts/' . $contract . '/revoke');
        if (!($response['success'] ?? false)) {
            if ($request->wantsJson()) {
                return response()->json($response, 422);
            }
            return back()->withErrors($this->api->extractErrors($response));
        }
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Contract revoked and reset to Draft.']);
        }
        return back()->with('success', 'Contract revoked and reset to Draft.');
    }

    public function streamPdf(int $contract, string $type = 'original')
    {
        return $this->api->forward('GET', '/admin/contracts/' . $contract . '/stream/' . $type, asJson: false);
    }

    public function download(int $contract, string $type = 'original')
    {
        return $this->api->forward('GET', '/admin/contracts/' . $contract . '/download/' . $type, asJson: false);
    }
}
