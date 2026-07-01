<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Admin\SupportTicketService;
use Illuminate\Http\Request;

class AdminTicketController extends Controller
{
    public function __construct(protected SupportTicketService $ticketService) {}

    public function index(Request $request)
    {
        $tickets = $this->ticketService->list($request->only(['search', 'status', 'priority']));
        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['tenant', 'creator', 'assignee', 'messages.sender']);
        $admins = User::whereIn('role', ['admin', 'superadmin'])->where('is_active', true)->get();
        return view('admin.tickets.show', compact('ticket', 'admins'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $request->validate(['message' => 'required|string']);
        $this->ticketService->addMessage($ticket, array_merge($request->all(), ['sender_id' => auth()->id(), 'sender_type' => 'admin']));
        return back()->with('success', 'Reply sent.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $request->validate(['status' => 'required|in:open,in_progress,waiting_customer,resolved,closed']);
        $this->ticketService->updateStatus($ticket, $request->status);
        return back()->with('success', 'Status updated.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $request->validate(['admin_id' => 'nullable|exists:users,id']);
        $ticket->update(['assigned_to' => $request->admin_id ?: null]);
        return back()->with('success', 'Ticket assigned.');
    }
}
