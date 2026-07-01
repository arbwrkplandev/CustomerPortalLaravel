<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\SupportTicket;
use App\Services\Admin\SupportTicketService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Customer - Support Tickets", description="Customer support ticket management")
 */
class SupportTicketController extends Controller
{
    use ApiResponse;

    public function __construct(protected SupportTicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = SupportTicket::where('tenant_id', Auth::user()->tenant_id)
            ->with(['assignee', 'messages'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->paginated($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'subject'     => 'required|string|max:255',
            'description' => 'required|string',
            'priority'    => 'nullable|in:low,medium,high,urgent',
            'category'    => 'nullable|in:billing,technical,subscription,contract,general',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $ticket = $this->ticketService->createTicket($request->all());
        return $this->created($ticket, 'Support ticket raised successfully');
    }

    public function show(int $id): JsonResponse
    {
        $ticket = SupportTicket::where('tenant_id', Auth::user()->tenant_id)
            ->with(['messages.sender', 'assignee'])
            ->findOrFail($id);

        // Mark messages as read
        $ticket->messages()
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success($ticket);
    }

    public function reply(Request $request, int $id): JsonResponse
    {
        $ticket = SupportTicket::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        // Re-open if closed/resolved
        if ($ticket->isResolved()) {
            $ticket->update(['status' => 'open']);
        }

        $message = $this->ticketService->addMessage($ticket, $request->all());
        return $this->created($message, 'Reply sent');
    }
}
