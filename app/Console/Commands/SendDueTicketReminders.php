<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Notifications\TicketDueSoonNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('app:send-due-ticket-reminders')]
#[Description('Send in-app notifications for tickets due within 24 hours.')]
class SendDueTicketReminders extends Command
{
    public function handle(): int
    {
        $sentCount = 0;
        $now = now();
        $reminderWindowEnds = $now->copy()->addDay();

        Ticket::query()
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->whereNotNull('engineer_id')
            ->whereNull('due_reminded_at')
            ->where('due_at', '>', $now)
            ->where('due_at', '<=', $reminderWindowEnds)
            ->select('id')
            ->chunkById(100, function (Collection $tickets) use (&$sentCount, $reminderWindowEnds): void {
                foreach ($tickets as $ticketRow) {
                    $sent = DB::transaction(function () use ($ticketRow, $reminderWindowEnds): bool {
                        $ticket = Ticket::query()
                            ->with('engineer')
                            ->whereKey($ticketRow->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            $ticket === null
                            || ! in_array($ticket->status, Ticket::ACTIVE_STATUSES, true)
                            || $ticket->engineer === null
                            || $ticket->due_at === null
                            || $ticket->due_at->lte(now())
                            || $ticket->due_at->gt($reminderWindowEnds)
                            || $ticket->due_reminded_at !== null
                        ) {
                            return false;
                        }

                        $ticket->engineer->notify(new TicketDueSoonNotification($ticket));
                        $ticket->update(['due_reminded_at' => now()]);

                        return true;
                    }, 3);

                    if ($sent) {
                        $sentCount++;
                    }
                }
            });

        $this->info("Sent {$sentCount} due ticket reminder(s).");

        return self::SUCCESS;
    }
}
