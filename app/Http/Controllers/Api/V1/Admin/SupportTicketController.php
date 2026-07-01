<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Services\Admin\SupportTicketService;
use App\Models\SupportTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(name="Admin - Support Tickets", description="Support ticket inbox management")
 */
class SupportTicketController extends Controller
{
    use ApiResponse;

    public function __construct(protected SupportTicketService $ticketService) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $this->ticketService->list(
            $request->only(['search', 'status', 'priority', 'tenant_id']),
            $request->integer('per_page', 15)
        );
        return $this->paginated($tickets);
    }

    public function show(SupportTicket $supportTicket): JsonResponse
    {
        return $this->success(
            $supportTicket->load(['tenant', 'creator', 'assignee', 'messages.sender'])
        );
    }

    public function reply(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message'     => 'required|string',
            'is_internal' => 'boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $message = $this->ticketService->addMessage($supportTicket, $request->all());
        return $this->created($message, 'Reply sent');
    }

    public function updateStatus(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,in_progress,waiting_response,resolved,closed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $ticket = $this->ticketService->updateStatus($supportTicket, $request->status);
        return $this->success($ticket, 'Status updated');
    }

    public function assign(Request $request, SupportTicket $supportTicket): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'admin_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $ticket = $this->ticketService->assign($supportTicket, $request->admin_id);
        return $this->success($ticket, 'Ticket assigned');
    }
}
