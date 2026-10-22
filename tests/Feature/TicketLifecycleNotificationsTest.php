<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketDueSoonNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketLifecycleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_creation_notifies_the_assigned_engineer(): void
    {
        $engineer = User::factory()->engineer()->create();
        Notification::fake();

        $this->actingAs($engineer)->post(route('tickets.store'), [
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Ragil',
            'description' => 'Koneksi internet terputus.',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'high',
        ])->assertRedirect();

        Notification::assertSentTo($engineer, TicketCreatedNotification::class);
    }

    public function test_manual_ticket_creation_notifies_the_creator_when_no_engineer_account_is_assigned(): void
    {
        $engineer = User::factory()->engineer()->create();
        Notification::fake();

        $this->actingAs($engineer)->post(route('tickets.store'), [
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Ragil',
            'description' => 'Koneksi internet terputus.',
            'assigned_engineer_name' => 'Affan',
            'status' => 'open',
            'priority' => 'high',
        ])->assertRedirect();

        Notification::assertSentTo($engineer, TicketCreatedNotification::class);
    }

    public function test_status_changes_notify_the_assigned_engineer(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'open']);
        Notification::fake();

        $this->actingAs($engineer)->put(route('tickets.update', $ticket), [
            'reporter_name' => $ticket->reporter_name,
            'location' => $ticket->location,
            'coordinator_name' => $ticket->coordinator_name,
            'description' => $ticket->description,
            'engineer_id' => $engineer->id,
            'status' => 'in_progress',
            'priority' => $ticket->priority,
            'handling_notes' => '',
        ])->assertRedirect(route('tickets.show', $ticket));

        Notification::assertSentTo($engineer, TicketStatusChangedNotification::class, fn (TicketStatusChangedNotification $notification): bool => $notification->previousStatus === 'open' && $notification->ticket->status === 'in_progress');
    }

    public function test_deleting_ticket_removes_its_notifications_and_activity_history(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $otherTicket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $ticket->activities()->create([
            'user_id' => $engineer->id,
            'action' => 'updated',
            'message' => 'Detail tiket diperbarui.',
        ]);
        $otherTicket->activities()->create([
            'user_id' => $engineer->id,
            'action' => 'updated',
            'message' => 'Aktivitas tiket lain.',
        ]);
        $engineer->notify(new TicketCreatedNotification($ticket));
        $engineer->notify(new TicketCreatedNotification($otherTicket));

        $this->actingAs($engineer)->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_activities', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_activities', ['ticket_id' => $otherTicket->id, 'message' => 'Aktivitas tiket lain.']);
        $this->assertFalse(DB::table('notifications')->where('data->ticket_id', $ticket->id)->exists());
        $this->assertTrue(DB::table('notifications')->where('data->ticket_id', $otherTicket->id)->exists());
    }

    public function test_dashboard_hides_activity_that_no_longer_has_a_ticket(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $ticket->activities()->create([
            'user_id' => $engineer->id,
            'action' => 'created',
            'message' => 'Aktivitas tiket aktif.',
        ]);
        TicketActivity::query()->create([
            'ticket_id' => null,
            'user_id' => $engineer->id,
            'action' => 'deleted',
            'message' => 'Aktivitas yatim yang tidak boleh tampil.',
        ]);

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Aktivitas tiket aktif.')
            ->assertDontSee('Aktivitas yatim yang tidak boleh tampil.');
    }

    public function test_dashboard_hides_notifications_for_missing_tickets(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);
        $engineer->notify(new TicketCreatedNotification($ticket));

        DB::table('tickets')->where('id', $ticket->id)->delete();

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Tiket baru ditugaskan');
    }

    public function test_bulk_status_changes_notify_each_assigned_engineer(): void
    {
        $engineer = User::factory()->engineer()->create();
        $firstTicket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'open']);
        $secondTicket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'pending']);
        Notification::fake();

        $this->actingAs($engineer)->post(route('tickets.bulk-action'), [
            'action' => 'update',
            'status' => 'resolved',
            'ticket_ids' => [$firstTicket->id, $secondTicket->id],
        ])->assertRedirect();

        Notification::assertSentToTimes($engineer, TicketStatusChangedNotification::class, 2);
    }

    public function test_ticket_due_within_24_hours_is_reminded_once(): void
    {
        $this->travelTo(now()->startOfMinute());
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'in_progress',
            'due_at' => now()->addHours(12),
            'due_reminded_at' => null,
        ]);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'closed',
            'due_at' => now()->addHours(12),
            'due_reminded_at' => null,
        ]);
        Notification::fake();

        Artisan::call('app:send-due-ticket-reminders');

        Notification::assertSentToTimes($engineer, TicketDueSoonNotification::class, 1);
        $this->assertNotNull($ticket->fresh()->due_reminded_at);

        Artisan::call('app:send-due-ticket-reminders');

        Notification::assertSentToTimes($engineer, TicketDueSoonNotification::class, 1);
    }
}
