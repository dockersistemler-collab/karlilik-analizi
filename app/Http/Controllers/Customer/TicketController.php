<?php

namespace App\Http\Controllers\Customer;

use App\Domain\Tickets\Actions\CreateTicketAction;
use App\Domain\Tickets\Actions\ReplyToTicketAction;
use App\Domain\Tickets\DTO\CreateTicketData;
use App\Domain\Tickets\DTO\ReplyTicketData;
use App\Domain\Tickets\Models\Ticket;
use App\Domain\Tickets\Models\TicketMessage;
use App\Domain\Tickets\Queries\TicketListQuery;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request, TicketListQuery $query): View
    {
        $filters = $request->only(['status', 'search']);
        $tickets = $query->forCustomer($request->user()->id, $filters)
            ->with(['assignedTo'])
            ->paginate(15)
            ->withQueryString();

        return view('admin.tickets.index', compact('tickets', 'filters'));
    }

    public function create(): View
    {
        return view('admin.tickets.create');
    }

    public function store(Request $request, CreateTicketAction $action): RedirectResponse
    {
        $validated = $request->validate(['subject' => 'required|string|max:255',
            'body' => 'required|string|max:5000',
            'priority' => 'required|in:low,medium,high,urgent',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        $user = $request->user();

        $ticket = $action->execute(new CreateTicketData(
            customerId: $user->id,
            createdById: $user->id,
            subject: $validated['subject'],
            body: $validated['body'],
            priority: $validated['priority'],
            channel: Ticket::CHANNEL_PANEL,
            attachments: $request->file('attachments', []),
        ));

        return redirect()->route('portal.tickets.show', $ticket)
            ->with('success', 'Destek talebiniz alındı.');
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['messages' => function ($query) {
                $query->where('is_internal', false)->with(['attachments', 'sender'])->orderBy('created_at');
            },
            'assignedTo',
        ]);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, Ticket $ticket, ReplyToTicketAction $action): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $validated = $request->validate(['body' => 'required|string|max:5000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:5120',
        ]);

        $user = $request->user();

        $action->execute(new ReplyTicketData(
            ticketId: $ticket->id,
            customerId: $ticket->customer_id,
            senderType: TicketMessage::SENDER_CUSTOMER,
            senderId: $user->id,
            body: $validated['body'],
            isInternal: false,
            attachments: $request->file('attachments', []),
        ));

        return redirect()->route('portal.tickets.show', $ticket)
            ->with('success', 'Yanıtınız gönderildi.');
    }
}




