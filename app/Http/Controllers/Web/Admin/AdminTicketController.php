<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index(Request $request)
    {
        $response = $this->api->get('/admin/tickets', [
            'search' => $request->query('search'),
            'status' => $request->query('status'),
            'priority' => $request->query('priority'),
            'per_page' => 15,
            'page' => $request->integer('page', 1),
        ]);

        $tickets = $this->api->toPaginator($response, 15);

        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(int $ticket)
    {
        $response = $this->api->get('/admin/tickets/' . $ticket);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $payload = $this->api->toEntities($response['data'] ?? []);
        $ticket = $payload?->ticket;
        $admins = $payload?->admins ?? [];

        return view('admin.tickets.show', compact('ticket', 'admins'));
    }

    public function reply(Request $request, int $ticket)
    {
        $request->validate(['message' => 'required|string']);

        $response = $this->api->post('/admin/tickets/' . $ticket . '/reply', array_merge($request->all(), [
            'sender_id' => auth()->id(),
            'sender_type' => 'admin',
        ]));

        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, int $ticket)
    {
        $request->validate(['status' => 'required|in:open,in_progress,waiting_response,resolved,closed']);

        $response = $this->api->patch('/admin/tickets/' . $ticket . '/status', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Status updated.');
    }

    public function assign(Request $request, int $ticket)
    {
        $request->validate(['admin_id' => 'required|integer']);

        $response = $this->api->patch('/admin/tickets/' . $ticket . '/assign', [
            'admin_id' => $request->integer('admin_id'),
        ]);
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Ticket assigned.');
    }
}
