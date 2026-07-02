<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\ApiEntity;
use App\Support\InternalApiGateway;

class AdminDashboardController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $response = $this->api->get('/admin/dashboard');
        $payload = $this->api->toEntities($response['data'] ?? []);

        $statsPayload = $payload?->stats;
        $statsSource = $statsPayload instanceof ApiEntity
            ? $statsPayload->toArray()
            : (array) ($statsPayload ?? []);

        $stats = array_merge([
            'total_tenants' => 0,
            'active_tenants' => 0,
            'open_tickets' => 0,
            'urgent_tickets' => 0,
            'signed_contracts' => 0,
            'pending_signature' => 0,
            'paid_invoices' => 0,
            'mtd_revenue' => 0,
        ], $statsSource);
        $recentTenants = $payload?->recent_tenants ?? [];
        $recentTickets = $payload?->recent_tickets ?? [];

        return view('admin.dashboard', compact('stats', 'recentTenants', 'recentTickets'));
    }
}
