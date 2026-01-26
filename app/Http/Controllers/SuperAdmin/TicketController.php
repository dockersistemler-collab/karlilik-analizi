<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Domain\Tickets\Actions\AssignTicketAction;
use App\Domain\Tickets\Actions\ChangeTicketStatusAction;
use App\Domain\Tickets\Actions\ReplyToTicketAction;
use App\Domain\Tickets\DTO\ReplyTicketData;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketMessage;
use App\Domain\Tickets\Queries\TicketListQuery;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    public function index(Request $request, TicketListQuery $query): View
    {
        $filters = $request->only(['status', 'search', 'customer_id', 'assigned_to_id']);
        $tickets = $query->forAdmin($filters)
            ->with(['customer', 'assignedTo'])
            ->paginate(20)
            ->withQueryString();

        $admins = User::query()->where('role', 'super_admin')->orderBy('name')->get();

        return view('super-admin.tickets.index', compact('tickets', 'filters', 'admins'));
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load([
            'messages' => function ($query) {
                $query->with(['attachments', 'sender'])->orderBy('created_at');
            },
            'customer',
            'assignedTo',
        ]);

        $admins = User::query()->where('role', 'super_admin')->orderBy('name')->get();

        return view('super-admin.tickets.show', compact('ticket', 'admins'));
    }

    public function reply(Request $request, Ticket $ticket, ReplyToTicketAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'body' => 'required|string|max:5000',
            'is_internal' => 'nullable|boolean',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        $user = $request->user();

        $action->execute(new ReplyTicketData(
            ticketId: $ticket->id,
            customerId: $ticket->customer_id,
            senderType: TicketMessage::SENDER_ADMIN,
            senderId: $user->id,
            body: $validated['body'],
            isInternal: $request->boolean('is_internal'),
            attachments: $request->file('attachments', []),
        ));

        return redirect()->route('super-admin.tickets.show', $ticket)
            ->with('success', 'Yanıt gönderildi.');
    }

    public function assign(Request $request, Ticket $ticket, AssignTicketAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to_id' => 'required|integer|exists:users,id',
        ]);

        $action->execute($ticket, (int) $validated['assigned_to_id'], $request->user());

        return redirect()->route('super-admin.tickets.show', $ticket)
            ->with('success', 'Talep atandı.');
    }

    public function changeStatus(Request $request, Ticket $ticket, ChangeTicketStatusAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:open,waiting_customer,waiting_admin,resolved,closed',
        ]);

        $action->execute($ticket, $validated['status'], $request->user(), TicketMessage::SENDER_ADMIN);

        return redirect()->route('super-admin.tickets.show', $ticket)
            ->with('success', 'Talep durumu güncellendi.');
    }
}


