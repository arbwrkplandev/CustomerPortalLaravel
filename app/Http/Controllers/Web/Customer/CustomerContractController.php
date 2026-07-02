<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class CustomerContractController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $response = $this->api->get('/customer/contracts', [
            'per_page' => 10,
            'page' => request()->integer('page', 1),
            'status' => request()->query('status'),
        ]);

        $contracts = $this->api->toPaginator($response, 10);

        return view('customer.contracts.index', compact('contracts'));
    }

    public function show(int $contract)
    {
        $response = $this->api->get('/customer/contracts/' . $contract);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $contract = $this->api->toEntities($response['data'] ?? []);

        return view('customer.contracts.show', compact('contract'));
    }

    public function sign(Request $request, int $contract)
    {
        $request->validate([
            'signature_image' => 'required|string',
            'signer_name'     => 'required|string|max:255',
        ]);

        $existing = $this->api->get('/customer/contracts/' . $contract);
        if (!($existing['success'] ?? false)) {
            abort(404);
        }

        $contractEntity = $this->api->toEntities($existing['data'] ?? []);
        $fields = [];
        foreach (($contractEntity?->signFields ?? []) as $field) {
            if (in_array($field->field_type, ['signature', 'initials'], true)) {
                $fields[$field->id] = $request->input('signature_image');
            } elseif ($request->has('field_' . $field->id)) {
                $fields[$field->id] = $request->input('field_' . $field->id);
            }
        }

        $response = $this->api->post('/customer/contracts/' . $contract . '/sign', [
            'signature_data' => $request->input('signature_image'),
            'signer_name' => $request->input('signer_name'),
            'fields' => $fields,
        ]);

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return redirect()->route('customer.contracts.show', $contract)
            ->with('signed', true);
    }

    public function uploadSigned(Request $request, int $contract)
    {
        $request->validate(['signed_file' => 'required|file|mimes:pdf|max:20480']);

        $response = $this->api->postWithFiles('/customer/contracts/' . $contract . '/upload-signed', [], [
            'signed_pdf' => $request->file('signed_file'),
        ]);
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return redirect()->route('customer.contracts.show', $contract)
            ->with('success', 'Signed contract uploaded successfully!');
    }

    public function streamPdf(int $contract, string $type = 'original')
    {
        return $this->api->forward('GET', '/customer/contracts/' . $contract . '/stream/' . $type, asJson: false);
    }

    public function download(int $contract, string $type = 'original')
    {
        return $this->api->forward('GET', '/customer/contracts/' . $contract . '/download/' . $type, asJson: false);
    }
}
