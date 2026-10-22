<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $request, string $notification): RedirectResponse
    {
        $notificationRecord = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $notificationRecord->markAsRead();

        $ticket = Ticket::query()->find($notificationRecord->data['ticket_id'] ?? null);

        return $ticket !== null
            ? redirect()->route('tickets.show', $ticket)
            : redirect()->route('dashboard');
    }
}
