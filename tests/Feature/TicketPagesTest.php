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
        Ticket::factory()->create([
            'project_name' => 'Network Refresh',
            'issue_category' => 'Incident Handling',
        ]);

        $this->actingAs($engineer)->get(route('tickets.index'))
            ->assertOk()
            ->assertSee('Tiket HelpDesk')
            ->assertDontSee('Records')
            ->assertDontSee('Workspace <span', false);
        $this->actingAs($engineer)->get(route('tickets.create'))
            ->assertOk()
            ->assertSee('Add New Ticket')
            ->assertSee('Ticket ID')
            ->assertSee('Generated automatically')
            ->assertSee('value="Network Refresh"', false)
            ->assertSee('id="project_select"', false)
            ->assertSee('Select a project...')
            ->assertSee('+ Add / Enter new project...')
            ->assertSee('name="project_name"', false)
            ->assertSee('value="Incident Handling"', false)
            ->assertSee('Device Issue')
            ->assertSee('Multiple choices supported.')
            ->assertSee('Coordinator')
            ->assertSee('Assigned Technicians')
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
            ->assertSee('Action Notes 1')
            ->assertSee('Activity Summary / Resume')
            ->assertDontSee('name="ticket_number"', false);
    }

    public function test_engineer_can_render_ticket_edit_form_with_existing_values(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create([
            'ticket_number' => 'TKT-EDIT-001',
            'reporter_name' => 'Faris',
            'location' => 'Ruang Server',
            'project_name' => 'Network Refresh',
            'coordinator_name' => 'Ragil, Ihsan',
            'devices' => 'Router, Switch',
            'technician_name' => 'Nalar, Hakim',
            'assigned_engineer_name' => 'Kadek',
            'description' => 'Router needs inspection.',
            'status' => 'pending',
            'priority' => 'high',
        ]);

        $this->actingAs($engineer)->get(route('tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Edit Ticket')
            ->assertSee('TKT-EDIT-001')
            ->assertSee('value="Ruang Server"', false)
            ->assertSee('value="Network Refresh"', false)
            ->assertSee('Router needs inspection.')
            ->assertSee('Ragil')
            ->assertSee('Ihsan')
            ->assertSee('Kadek')
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('name="assigned_engineer_name"', false)
            ->assertSee('Save Changes');
    }

    public function test_engineer_can_assign_a_ticket_to_a_fixed_responsible_engineer_option(): void
    {
        $user = User::factory()->engineer()->create();

        $response = $this->actingAs($user)->post(route('tickets.store'), [
            'reporter_name' => 'Faris',
            'location' => 'BKN Mataram',
            'coordinator_name' => 'Ragil, Ihsan',
            'description' => 'Router perlu pengecekan konfigurasi.',
            'assigned_engineer_name' => 'Kadek',
            'technician_name' => 'Kadek, Nalar, Hakim',
            'devices' => 'Router, Switch',
            'status' => 'open',
            'priority' => 'high',
        ]);

        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('ticket_feedback', 'Ticket berhasil dibuat.');

        $this->actingAs($user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ticket berhasil dibuat.')
            ->assertSee('setTimeout(() => visible = false, 2000)', false)
            ->assertSee('fixed left-1/2 top-1/2', false);

        $ticket = Ticket::query()->sole();

        $this->assertSame('Kadek', $ticket->assigned_engineer_name);
        $this->assertSame('Ragil, Ihsan', $ticket->coordinator_name);
        $this->assertSame('Kadek, Nalar, Hakim', $ticket->technician_name);
        $this->assertSame('Router, Switch', $ticket->devices);
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
