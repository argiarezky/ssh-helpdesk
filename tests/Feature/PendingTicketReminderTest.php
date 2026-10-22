<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\PendingTicketReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PendingTicketReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_tickets_are_reminded_once_every_24_hours(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $pendingTicket = Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'pending',
            'pending_reminded_at' => null,
        ]);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'pending_reminded_at' => null,
        ]);
        Notification::fake();

        Artisan::call('app:send-pending-ticket-reminders');

        Notification::assertSentToTimes($engineer, PendingTicketReminder::class, 1);
        $this->assertNotNull($pendingTicket->fresh()->pending_reminded_at);

        $this->travel(23)->hours();
        Artisan::call('app:send-pending-ticket-reminders');

        Notification::assertSentToTimes($engineer, PendingTicketReminder::class, 1);

        $this->travel(1)->hours();
        Artisan::call('app:send-pending-ticket-reminders');

        Notification::assertSentToTimes($engineer, PendingTicketReminder::class, 2);
    }

    public function test_engineer_can_open_a_pending_ticket_notification(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $ticket = Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'pending',
        ]);
        $engineer->notify(new PendingTicketReminder($ticket));
        $notification = $engineer->notifications()->firstOrFail();

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Notifikasi')
            ->assertSee('Pengingat: tiket masih Pending');

        $this->post(route('notifications.read', $notification->id))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
