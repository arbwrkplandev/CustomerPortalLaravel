<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminAuditController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $response = $this->api->get('/admin/audit', [
            'search' => $request->query('search'),
            'module' => $request->query('module'),
            'date_from' => $request->query('date_from'),
            'date_to' => $request->query('date_to'),
            'per_page' => 20,
            'page' => $request->integer('page', 1),
        ]);

        $logs = $this->api->toPaginator($response, 20);
        $modules = collect($logs->items())
            ->pluck('module')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.audit.index', compact('logs', 'modules'));
    }
}
