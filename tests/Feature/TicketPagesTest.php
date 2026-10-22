<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_render_ticket_list_and_create_form(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Tiket HelpDesk')
            ->assertDontSee('Records')
            ->assertDontSee('Workspace <span', false);
        $this->actingAs($engineer)->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Nomor tiket')
            ->assertSee('Dibuat otomatis setelah tiket disimpan')
            ->assertSee('Ragil')
            ->assertSee('Ihsan')
            ->assertSee('Kholil')
            ->assertSee('Reggy')
            ->assertSee('Nino')
            ->assertSee('Affan')
            ->assertSee('Nalar')
            ->assertSee('Kadek')
            ->assertSee('Hakim')
            ->assertSee('Hari')
            ->assertSee('Rasyid')
            ->assertSee('Irzam')
            ->assertDontSee('Argia Rezky Pratama')
            ->assertSee('Catatan penanganan')
            ->assertDontSee('name="ticket_number"', false);
    }

    public function test_engineer_can_assign_a_ticket_to_a_fixed_responsible_engineer_option(): void
    {
        $user = User::factory()->engineer()->create();

        $this->actingAs($user)->post(route('tickets.store'), [
            'reporter_name' => 'Faris',
            'location' => 'BKN Mataram',
            'coordinator_name' => 'Ragil',
            'description' => 'Router perlu pengecekan konfigurasi.',
            'assigned_engineer_name' => 'Kadek',
            'technician_name' => 'Nalar, Hakim',
            'status' => 'open',
            'priority' => 'high',
        ])->assertRedirect();

        $ticket = Ticket::query()->sole();

        $this->assertSame('Kadek', $ticket->assigned_engineer_name);
        $this->assertSame('Nalar, Hakim', $ticket->technician_name);
        $this->assertNull($ticket->engineer_id);
    }

    public function test_ticket_list_filters_by_responsible_engineer_name(): void
    {
        $user = User::factory()->engineer()->create();
        $matchingTicket = Ticket::factory()->create([
            'ticket_number' => 'TKT-ENGINEER-KADEK',
            'assigned_engineer_name' => 'Kadek',
        ]);
        Ticket::factory()->create([
            'ticket_number' => 'TKT-ENGINEER-AFFAN',
            'assigned_engineer_name' => 'Affan',
        ]);

        $this->actingAs($user)->get(route('tickets.index', ['responsible_engineer' => 'Kadek']))
            ->assertOk()
            ->assertSee($matchingTicket->ticket_number)
            ->assertDontSee('TKT-ENGINEER-AFFAN');
    }

    public function test_engineer_can_render_ticket_detail(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'SSH-VIEW-001',
        ]);

        $this->actingAs($engineer)->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('SSH-VIEW-001')
            ->assertSee('Informasi masalah');
    }

    public function test_ticket_list_filters_by_status_and_search_term(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'SSH-FILTER-OPEN',
            'status' => 'open',
            'reporter_name' => 'Rina Putri',
        ]);
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'ticket_number' => 'SSH-FILTER-CLOSED',
            'status' => 'closed',
            'reporter_name' => 'Budi Santoso',
        ]);

        $this->actingAs($engineer)->get(route('tickets.index', ['status' => 'open', 'search' => 'Rina']))
            ->assertOk()
            ->assertSee('SSH-FILTER-OPEN')
            ->assertDontSee('SSH-FILTER-CLOSED')
            ->assertSee('Pilih tiket')
            ->assertSee('Pilih semua')
            ->assertSee('Ubah status')
            ->assertSee('Hapus pilihan')
            ->assertSee('name="action" value="delete" formnovalidate', false)
            ->assertSee(route('tickets.bulk-action'));
    }
}
