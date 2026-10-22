<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HelpdeskAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_dashboard_and_ticket_routes(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('tickets.index'))->assertRedirect(route('login'));
        $this->post(route('tickets.store'))->assertRedirect(route('login'));
        $this->post(route('tickets.bulk-action'))->assertRedirect(route('login'));
    }

    public function test_non_engineer_cannot_access_dashboard_or_tickets(): void
    {
        $user = User::factory()->create(['role' => 'support']);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($user)->get(route('tickets.index'))->assertForbidden();
        $this->actingAs($user)->post(route('tickets.store'))->assertForbidden();
        $this->actingAs($user)->post(route('tickets.bulk-action'))->assertForbidden();
    }

    public function test_registration_route_is_unavailable(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
    }

    public function test_engineer_can_log_in(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer', 'password' => bcrypt('EngineerPass123!')]);

        $this->post(route('login'), ['email' => $engineer->email, 'password' => 'EngineerPass123!'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($engineer);
    }

    public function test_non_engineer_cannot_log_in(): void
    {
        $user = User::factory()->create(['role' => 'support', 'password' => bcrypt('EngineerPass123!')]);

        $this->from(route('login'))->post(route('login'), ['email' => $user->email, 'password' => 'EngineerPass123!'])
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_engineer_can_create_and_update_ticket(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $ticketData = [
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Budi Santoso',
            'description' => 'Koneksi internet tidak stabil.',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'high',
            'created_at' => '2026-10-20T09:30',
            'handling_notes' => '',
        ];

        $this->actingAs($engineer)->post(route('tickets.store'), $ticketData)->assertRedirect();
        $ticket = Ticket::query()->where('ticket_number', 'TKT-001')->firstOrFail();
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'open', 'reporter_name' => 'Rina Putri']);
        $this->assertSame('2026-10-20 09:30:00', $ticket->fresh()->created_at->format('Y-m-d H:i:s'));

        $updatedData = array_merge($ticketData, ['ticket_number' => 'TKT-999', 'status' => 'resolved', 'handling_notes' => 'Modem direstart.']);
        $this->put(route('tickets.update', $ticket), $updatedData)->assertRedirect(route('tickets.show', $ticket));
        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'ticket_number' => 'TKT-001', 'status' => 'resolved', 'handling_notes' => 'Modem direstart.']);
    }

    public function test_ticket_number_is_generated_and_cannot_be_submitted_manually(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $data = [
            'ticket_number' => 'TKT-900',
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Budi Santoso',
            'description' => 'Koneksi internet tidak stabil.',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'high',
            'handling_notes' => '',
        ];

        $this->actingAs($engineer)->from(route('tickets.create'))->post(route('tickets.store'), $data)
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', ['ticket_number' => 'TKT-001']);
        $this->assertDatabaseMissing('tickets', ['ticket_number' => 'TKT-900']);
    }

    public function test_ticket_status_must_be_allowed(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);

        $this->actingAs($engineer)->from(route('tickets.create'))->post(route('tickets.store'), [
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Budi Santoso',
            'description' => 'Koneksi internet tidak stabil.',
            'engineer_id' => $engineer->id,
            'status' => 'unknown',
            'priority' => 'high',
        ])->assertSessionHasErrors(['status']);

        $this->assertDatabaseCount('tickets', 0);
    }

    public function test_duplicate_active_ticket_is_rejected_but_closed_history_can_be_reported_again(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $ticketData = [
            'reporter_name' => 'Rina Putri',
            'location' => 'Jakarta Selatan',
            'coordinator_name' => 'Ragil',
            'description' => 'Koneksi internet tidak stabil.',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'normal',
        ];

        $existingTicket = Ticket::factory()->create($ticketData);

        $this->actingAs($engineer)->from(route('tickets.create'))->post(route('tickets.store'), $ticketData)
            ->assertRedirect(route('tickets.create'))
            ->assertSessionHasErrors(['description']);

        $this->assertDatabaseCount('tickets', 1);

        $existingTicket->update(['status' => 'closed']);

        $this->actingAs($engineer)->post(route('tickets.store'), $ticketData)->assertRedirect();

        $this->assertDatabaseCount('tickets', 2);
    }

    public function test_ticket_numbers_are_padded_and_unique_through_the_tenth_ticket(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);

        for ($ticketNumber = 1; $ticketNumber <= 10; $ticketNumber++) {
            $this->actingAs($engineer)->post(route('tickets.store'), [
                'reporter_name' => 'Reporter '.$ticketNumber,
                'location' => 'Jakarta',
                'coordinator_name' => 'Coordinator',
                'description' => 'Network issue '.$ticketNumber,
                'engineer_id' => $engineer->id,
                'status' => 'open',
                'priority' => 'normal',
            ])->assertRedirect();
        }

        $this->assertSame(
            ['TKT-001', 'TKT-002', 'TKT-003', 'TKT-004', 'TKT-005', 'TKT-006', 'TKT-007', 'TKT-008', 'TKT-009', 'TKT-010'],
            Ticket::query()->orderBy('id')->pluck('ticket_number')->all(),
        );
        $this->assertSame(10, Ticket::query()->distinct()->count('ticket_number'));
    }

    public function test_ticket_number_format_supports_three_digit_hundreds(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);

        foreach (range(1, 99) as $number) {
            Ticket::factory()->create([
                'ticket_number' => 'TKT-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
                'engineer_id' => $engineer->id,
            ]);
        }

        $ticket = $this->createTicket($engineer, 100);

        $this->assertSame('TKT-100', $ticket->ticket_number);
        $this->assertSame(100, Ticket::query()->distinct()->count('ticket_number'));
    }

    public function test_deleting_a_ticket_resequences_numbers_without_changing_primary_keys_or_engineers(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $tickets = collect(range(1, 4))->map(fn (int $number): Ticket => Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'SSH-LEGACY-00'.$number,
        ]));
        $deletedTicket = $tickets[1];
        $remainingTicketIds = [$tickets[0]->id, $tickets[2]->id, $tickets[3]->id];

        $this->actingAs($engineer)->delete(route('tickets.destroy', $deletedTicket))
            ->assertRedirect(route('tickets.index'));

        $this->assertSame($remainingTicketIds, Ticket::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(['TKT-001', 'TKT-002', 'TKT-003'], Ticket::query()->orderBy('id')->pluck('ticket_number')->all());
        $this->assertSame(3, Ticket::query()->distinct()->count('ticket_number'));
        $this->assertDatabaseHas('tickets', ['id' => $tickets[2]->id, 'engineer_id' => $engineer->id, 'ticket_number' => 'TKT-002']);

        $this->actingAs($engineer)->get(route('tickets.show', $tickets[2]))
            ->assertOk()
            ->assertSee('TKT-002');

        $newTicket = $this->createTicket($engineer, 5);

        $this->assertSame('TKT-004', $newTicket->ticket_number);
    }

    public function test_ticket_numbering_restarts_after_all_tickets_are_deleted(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $tickets = collect(range(1, 2))->map(fn (int $number): Ticket => $this->createTicket($engineer, $number));

        foreach ($tickets as $ticket) {
            $this->actingAs($engineer)->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));
        }

        $newTicket = $this->createTicket($engineer, 3);

        $this->assertSame('TKT-001', $newTicket->ticket_number);
        $this->assertSame(1, Ticket::query()->distinct()->count('ticket_number'));
    }

    public function test_engineer_can_delete_ticket(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);

        $this->actingAs($engineer)->delete(route('tickets.destroy', $ticket))->assertRedirect(route('tickets.index'));
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }

    public function test_engineer_can_update_status_for_selected_tickets(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $tickets = collect(range(1, 2))->map(fn (int $number): Ticket => Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'status' => 'open',
        ]));

        $this->actingAs($engineer)->post(route('tickets.bulk-action'), [
            'action' => 'update',
            'status' => 'resolved',
            'ticket_ids' => $tickets->pluck('id')->all(),
        ])->assertRedirect();

        foreach ($tickets as $ticket) {
            $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'resolved']);
        }
    }

    public function test_engineer_can_delete_selected_tickets_and_ticket_numbers_are_resequenced(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $tickets = collect(range(1, 4))->map(fn (int $number): Ticket => Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'TKT-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
        ]));
        $deletedTicketIds = [$tickets[1]->id, $tickets[3]->id];

        $this->actingAs($engineer)->post(route('tickets.bulk-action'), [
            'action' => 'delete',
            'status' => '',
            'ticket_ids' => $deletedTicketIds,
        ])->assertRedirect();

        $this->assertSame([$tickets[0]->id, $tickets[2]->id], Ticket::query()->orderBy('id')->pluck('id')->all());
        $this->assertSame(['TKT-001', 'TKT-002'], Ticket::query()->orderBy('id')->pluck('ticket_number')->all());
        $this->assertSame(2, Ticket::query()->distinct()->count('ticket_number'));
        $this->assertDatabaseMissing('tickets', ['id' => $tickets[1]->id]);
        $this->assertDatabaseMissing('tickets', ['id' => $tickets[3]->id]);
    }

    public function test_bulk_ticket_action_rejects_invalid_ids_and_statuses(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id]);

        $this->actingAs($engineer)->from(route('tickets.index'))->post(route('tickets.bulk-action'), [
            'action' => 'update',
            'status' => 'unknown',
            'ticket_ids' => [$ticket->id, 999999],
        ])->assertRedirect(route('tickets.index'))
            ->assertSessionHasErrors(['ticket_ids.1', 'status']);

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_dashboard_uses_ticket_records_from_database(): void
    {
        $engineer = User::factory()->create(['role' => 'engineer']);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'TKT-DASH-001',
            'coordinator_name' => 'Ada Lovelace',
            'priority' => 'low',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'TKT-DASH-002',
            'coordinator_name' => 'Alan Turing',
            'priority' => 'high',
            'status' => 'open',
        ]);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'TKT-DASH-003',
            'coordinator_name' => 'Ada Lovelace',
            'priority' => 'urgent',
            'status' => 'resolved',
        ]);

        $this->actingAs($engineer)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Overview')
            ->assertDontSee('Workspace <span', false)
            ->assertSee('Status Breakdown')
            ->assertSee('Koordinator')
            ->assertSee('Priority Breakdown')
            ->assertSee('Ada Lovelace')
            ->assertSee('Critical')
            ->assertSee('data-label="Ada Lovelace" data-count="2"', false)
            ->assertSee('data-label="Critical" data-count="1"', false)
            ->assertSee('data-label="Medium" data-count="0"', false)
            ->assertSee('fill="#16A34A"', false)
            ->assertSee('fill="#EAB308"', false)
            ->assertSee('fill="#EA580C"', false)
            ->assertSee('fill="#DC2626"', false)
            ->assertSee('transform="translate(0, 12)"', false);
        $this->assertDatabaseCount('tickets', 3);
    }

    private function createTicket(User $engineer, int $number): Ticket
    {
        $this->actingAs($engineer)->post(route('tickets.store'), [
            'reporter_name' => 'Reporter '.$number,
            'location' => 'Jakarta',
            'coordinator_name' => 'Coordinator',
            'description' => 'Network issue '.$number,
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'normal',
        ])->assertRedirect();

        return Ticket::query()->latest('id')->firstOrFail();
    }
}
