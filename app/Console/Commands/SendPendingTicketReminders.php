<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Notifications\PendingTicketReminder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

#[Signature('app:send-pending-ticket-reminders')]
#[Description('Send due in-app reminders for pending tickets.')]
class SendPendingTicketReminders extends Command
{
    public function handle(): int
    {
        $sentCount = 0;
        $dueBefore = now()->subDay();

        Ticket::query()
            ->where('status', 'pending')
            ->whereNotNull('engineer_id')
            ->where(function (Builder $query) use ($dueBefore): void {
                $query->whereNull('pending_reminded_at')
                    ->orWhere('pending_reminded_at', '<=', $dueBefore);
            })
            ->select('id')
            ->chunkById(100, function (Collection $tickets) use (&$sentCount): void {
                foreach ($tickets as $ticketRow) {
                    $sent = DB::transaction(function () use ($ticketRow): bool {
                        $ticket = Ticket::query()
                            ->with('engineer')
                            ->whereKey($ticketRow->id)
                            ->lockForUpdate()
                            ->first();

                        if (
                            $ticket === null
                            || $ticket->status !== 'pending'
                            || $ticket->engineer === null
                            || ($ticket->pending_reminded_at !== null && $ticket->pending_reminded_at->gt(now()->subDay()))
                        ) {
                            return false;
                        }

                        $ticket->engineer->notify(new PendingTicketReminder($ticket));
                        $ticket->update(['pending_reminded_at' => now()]);

                        return true;
                    }, 3);

                    if ($sent) {
                        $sentCount++;
                    }
                }
            });

        $this->info("Sent {$sentCount} pending ticket reminder(s).");

        return self::SUCCESS;
    }
}
