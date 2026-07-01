<?php

namespace App\Services\Admin;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use Illuminate\Support\Str;

class SupportTicketService
{
    public function list(array $filters = [], int $perPage = 15)
    {
        $query = SupportTicket::with(['tenant', 'creator', 'assignee']);

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $query->where(function ($q) use ($s) {
                $q->where('ticket_number', 'like', "%$s%")
                  ->orWhere('subject', 'like', "%$s%");
            });
        }
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['priority'])) $query->where('priority', $filters['priority']);
        if (!empty($filters['tenant_id'])) $query->where('tenant_id', $filters['tenant_id']);

        return $query->latest()->paginate($perPage);
    }

    public function createTicket(array $data): SupportTicket
    {
        $ticket = SupportTicket::create([
            'tenant_id'   => auth()->user()->tenant_id,
            'created_by'  => auth()->id(),
            'ticket_number' => 'TKT-' . strtoupper(Str::random(8)),
            'subject'     => $data['subject'],
            'description' => $data['description'],
            'priority'    => $data['priority'] ?? 'medium',
            'category'    => $data['category'] ?? 'general',
            'status'      => 'open',
        ]);

        // Add initial message
        SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => auth()->id(),
            'sender_type' => 'customer',
            'message'     => $data['description'],
        ]);

        return $ticket->load(['tenant', 'creator']);
    }

    public function addMessage(SupportTicket $ticket, array $data): SupportTicketMessage
    {
        $senderType = auth()->user()->isAdmin() ? 'admin' : 'customer';

        // Set first_response_at if admin replies first time
        if ($senderType === 'admin' && !$ticket->first_response_at) {
            $ticket->update([
                'first_response_at' => now(),
                'status'            => 'in_progress',
            ]);
        }

        return SupportTicketMessage::create([
            'ticket_id'   => $ticket->id,
            'sender_id'   => auth()->id(),
            'sender_type' => $senderType,
            'message'     => $data['message'],
            'is_internal' => $data['is_internal'] ?? false,
        ]);
    }

    public function updateStatus(SupportTicket $ticket, string $status): SupportTicket
    {
        $update = ['status' => $status];
        if (in_array($status, ['resolved', 'closed'])) {
            $update['resolved_at'] = now();
        }
        $ticket->update($update);
        return $ticket->fresh();
    }

    public function assign(SupportTicket $ticket, int $adminId): SupportTicket
    {
        $ticket->update(['assigned_to' => $adminId]);
        return $ticket->fresh(['assignee']);
    }
}
