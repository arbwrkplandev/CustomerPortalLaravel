<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\Admin\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerTicketController extends Controller
{
    public function __construct(protected SupportTicketService $ticketService) {}

    public function index()
    {
        $tickets = SupportTicket::where('tenant_id', Auth::user()->tenant_id)
            ->with(['messages'])->latest()->paginate(10);
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
        $ticket = $this->ticketService->createTicket($request->all());
        return redirect()->route('customer.tickets.show', $ticket)->with('success', 'Ticket submitted!');
    }

    public function show(SupportTicket $ticket)
    {
        abort_unless($ticket->tenant_id === Auth::user()->tenant_id, 403);
        $ticket->load(['messages.sender', 'assignee']);
        // Mark admin messages as read
        $ticket->messages()->where('sender_type', 'admin')->whereNull('read_at')->update(['read_at' => now()]);
        return view('customer.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->tenant_id === Auth::user()->tenant_id, 403);
        $request->validate(['message' => 'required|string']);
        $this->ticketService->addMessage($ticket, $request->all());
        return back()->with('success', 'Reply sent.');
    }
}
