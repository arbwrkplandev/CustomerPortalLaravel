<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Support\InternalApiGateway;
use Illuminate\Http\Request;

class CustomerTicketController extends Controller
{
    public function __construct(protected InternalApiGateway $api) {}

    public function index()
    {
        $response = $this->api->get('/customer/tickets', [
            'per_page' => 10,
            'page' => request()->integer('page', 1),
            'status' => request()->query('status'),
        ]);

        $tickets = $this->api->toPaginator($response, 10);

        return view('customer.tickets.index', compact('tickets'));
    }

    public function create() { return view('customer.tickets.create'); }

    public function store(Request $request)
    {
        $request->validate([
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'category'    => 'nullable|in:billing,technical,subscription,contract,general',
            'priority'    => 'nullable|in:low,medium,high,urgent',
        ]);

        $response = $this->api->post('/customer/tickets', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        $ticket = $this->api->toEntities($response['data'] ?? []);
        return redirect()->route('customer.tickets.show', $ticket->id)->with('success', 'Ticket submitted!');
    }

    public function show(int $ticket)
    {
        $response = $this->api->get('/customer/tickets/' . $ticket);
        if (!($response['success'] ?? false)) {
            abort(404);
        }

        $ticket = $this->api->toEntities($response['data'] ?? []);

        return view('customer.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, int $ticket)
    {
        $request->validate(['message' => 'required|string']);

        $response = $this->api->post('/customer/tickets/' . $ticket . '/reply', $request->all());
        if (!($response['success'] ?? false)) {
            return back()->withErrors($this->api->extractErrors($response))->withInput();
        }

        return back()->with('success', 'Reply sent.');
    }
}
