<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Notifications\Notification;

class TicketStatusChangedNotification extends Notification
{
    public function __construct(public Ticket $ticket, public string $previousStatus) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Status tiket diperbarui',
            'message' => $this->ticket->ticket_number.' berubah dari '.ucfirst(str_replace('_', ' ', $this->previousStatus)).' menjadi '.ucfirst(str_replace('_', ' ', $this->ticket->status)).'.',
            'ticket_id' => $this->ticket->id,
            'url' => route('tickets.show', $this->ticket),
        ];
    }
}
