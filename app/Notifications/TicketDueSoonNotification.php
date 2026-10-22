<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketDueSoonNotification extends Notification
{
    public function __construct(public Ticket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Tenggat tiket mendekat',
            'message' => $this->ticket->ticket_number.' jatuh tempo pada '.$this->ticket->due_at?->format('d/m/Y H:i').'.',
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
