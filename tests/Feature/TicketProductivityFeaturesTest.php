<?php

namespace Tests\Feature;

use App\Models\ResponseTemplate;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketProductivityFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_filters_and_exact_ticket_number_search_work(): void
    {
        $engineer = User::factory()->engineer()->create();
        $matching = Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-701', 'priority' => 'high', 'status' => 'open']);
        Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-702', 'priority' => 'low', 'status' => 'closed']);

        $this->actingAs($engineer)->get(route('tickets.index', ['priority' => 'high', 'status' => 'open']))
            ->assertOk()->assertSee('TKT-701')->assertDontSee('TKT-702');
        $this->get(route('tickets.index', ['search' => 'TKT-701']))->assertRedirect(route('tickets.show', $matching));
    }

    public function test_select_all_matching_updates_tickets_across_pages(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->count(12)->create(['engineer_id' => $engineer->id, 'status' => 'open']);

        $this->actingAs($engineer)->post(route('tickets.bulk-action'), [
            'action' => 'update',
            'status' => 'resolved',
            'select_all_matching' => '1',
            'filters' => ['status' => 'open', 'archived' => ''],
        ])->assertRedirect();

        $this->assertSame(12, Ticket::query()->where('status', 'resolved')->count());
    }

    public function test_archived_tickets_are_hidden_until_restored(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-ARCHIVE']);

        $this->actingAs($engineer)->post(route('tickets.archive', $ticket))->assertRedirect(route('tickets.index'));
        $this->assertNotNull($ticket->fresh()->archived_at);
        $this->assertSame(1, Ticket::query()->whereNotNull('archived_at')->count());
        $this->get(route('tickets.index'))->assertViewHas('tickets', fn (LengthAwarePaginator $tickets): bool => $tickets->total() === 0);
        $archivedList = $this->get(route('tickets.index', ['archived' => '1']));
        $archivedList->assertViewHas('filters', fn (array $filters): bool => $filters['archived'] === '1')
            ->assertSee('TKT-ARCHIVE')->assertSee('Pulihkan');
        $this->post(route('tickets.restore', $ticket))->assertRedirect(route('tickets.show', $ticket));
        $this->assertNull($ticket->fresh()->archived_at);
    }

    public function test_engineer_can_claim_an_unassigned_ticket_and_view_history(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => null]);

        $this->actingAs($engineer)->post(route('tickets.claim', $ticket))->assertRedirect();
        $this->assertSame($engineer->id, $ticket->fresh()->engineer_id);
        $this->get(route('tickets.show', $ticket))->assertSee('Tiket diambil oleh '.$engineer->name)->assertSee('Riwayat aktivitas');
    }

    public function test_ticket_export_returns_filtered_csv(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-CSV-1', 'priority' => 'high', 'status' => 'in_progress']);
        Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-CSV-2', 'priority' => 'low']);

        $response = $this->actingAs($engineer)->get(route('tickets.export', ['priority' => 'high']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $csv = $response->streamedContent();
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString('Nomor Tiket', $csv);
        $this->assertStringContainsString('TKT-CSV-1', $csv);
        $this->assertStringContainsString('High', $csv);
        $this->assertStringContainsString('In Progress', $csv);
        $this->assertStringNotContainsString('in_progress', $csv);
        $this->assertStringNotContainsString('TKT-CSV-2', $csv);
    }

    public function test_excel_export_returns_a_formatted_filtered_spreadsheet(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->create(['ticket_number' => 'TKT-XLS-1', 'priority' => 'high', 'description' => '<script>alert(1)</script>']);
        Ticket::factory()->create(['ticket_number' => 'TKT-XLS-2', 'priority' => 'low']);

        $response = $this->actingAs($engineer)->get(route('tickets.export.excel', ['priority' => 'high']));
        $spreadsheet = $response->streamedContent();

        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringStartsWith('PK', $spreadsheet);

        $workbookPath = tempnam(sys_get_temp_dir(), 'ssh-helpdesk-test-');
        file_put_contents($workbookPath, $spreadsheet);
        $archive = new \ZipArchive;
        $this->assertSame(true, $archive->open($workbookPath));
        $worksheet = $archive->getFromName('xl/worksheets/sheet1.xml');
        $styles = $archive->getFromName('xl/styles.xml');
        $archive->close();
        unlink($workbookPath);

        $this->assertStringContainsString('SSH HelpDesk · Network Operations Center', $worksheet);
        $this->assertStringContainsString('Nomor Tiket', $worksheet);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $worksheet);
        $this->assertStringContainsString('FF17324B', $styles);
        $this->assertStringNotContainsString('TKT-XLS-2', $worksheet);
    }

    public function test_response_template_can_be_saved_and_used_in_ticket_form(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->post(route('response-templates.store'), [
            'title' => 'Restart perangkat',
            'content' => 'Perangkat telah dimulai ulang.',
        ])->assertRedirect();

        $template = ResponseTemplate::query()->firstOrFail();
        $this->get(route('tickets.create'))->assertOk()->assertSee('Restart perangkat')->assertSee('Perangkat telah dimulai ulang.');
        $this->put(route('response-templates.update', $template), ['title' => 'Restart router', 'content' => 'Router berhasil dimulai ulang.'])->assertRedirect();
        $this->assertDatabaseHas('response_templates', ['id' => $template->id, 'title' => 'Restart router']);
    }

    public function test_csv_and_print_tools_and_activity_panel_render_on_ticket_detail(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'ticket_number' => 'TKT-TOOLS-1']);

        $this->actingAs($engineer)->get(route('tickets.show', $ticket))
            ->assertOk()->assertSee('Salin nomor')->assertSee('Cetak')->assertSee('Riwayat aktivitas');
    }

    public function test_similar_active_ticket_for_same_reporter_and_location_is_rejected(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->create([
            'engineer_id' => $engineer->id,
            'reporter_name' => 'Rina Putri',
            'location' => 'Ruang Meeting 1',
            'description' => 'Internet lemot di ruang meeting utama',
            'status' => 'open',
        ]);

        $this->actingAs($engineer)->from(route('tickets.create'))->post(route('tickets.store'), [
            'reporter_name' => 'Rina Putri',
            'location' => 'Ruang Meeting 1',
            'coordinator_name' => 'Ragil',
            'description' => 'Internet lemo di ruang meeting utama',
            'engineer_id' => $engineer->id,
            'status' => 'open',
            'priority' => 'normal',
        ])->assertRedirect(route('tickets.create'))->assertSessionHasErrors('description');
    }

    public function test_old_active_tickets_show_a_stale_indicator(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'pending']);
        Ticket::query()->whereKey($ticket->id)->update(['updated_at' => now()->subDays(4)]);

        $this->actingAs($engineer)->get(route('tickets.index'))->assertSee('Lama');
    }

    public function test_handling_note_changes_are_kept_in_history_and_dashboard_activity(): void
    {
        $engineer = User::factory()->engineer()->create();
        $ticket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'handling_notes' => null]);
        $data = [
            'reporter_name' => $ticket->reporter_name,
            'location' => $ticket->location,
            'coordinator_name' => $ticket->coordinator_name,
            'description' => $ticket->description,
            'engineer_id' => $engineer->id,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'handling_notes' => 'Pemeriksaan pertama selesai.',
        ];

        $this->actingAs($engineer)->put(route('tickets.update', $ticket), $data)->assertRedirect();
        $this->put(route('tickets.update', $ticket), [...$data, 'handling_notes' => 'Kabel diganti, koneksi normal.'])->assertRedirect();

        $this->get(route('tickets.show', $ticket))->assertSee('Pemeriksaan pertama selesai.')->assertSee('Kabel diganti, koneksi normal.');
        $this->get(route('dashboard'))->assertSee('Catatan penanganan ditambahkan.');
    }

    public function test_dashboard_date_filter_only_counts_tickets_in_the_selected_range(): void
    {
        $engineer = User::factory()->engineer()->create();
        Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'open']);
        $oldTicket = Ticket::factory()->create(['engineer_id' => $engineer->id, 'status' => 'pending']);
        DB::table('tickets')->where('id', $oldTicket->id)->update(['created_at' => now()->subDays(10)]);

        $this->actingAs($engineer)->get(route('dashboard', ['from' => now()->toDateString(), 'to' => now()->toDateString()]))
            ->assertViewHas('totalTickets', 1)
            ->assertViewHas('statusCounts', fn (Collection $statusCounts): bool => $statusCounts['open'] === 1 && $statusCounts['pending'] === 0);
    }

    public function test_ticket_list_remembers_last_filters(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->get(route('tickets.index', ['priority' => 'high', 'status' => 'open']))->assertOk();
        $this->get(route('tickets.index'))->assertRedirect(route('tickets.index', ['status' => 'open', 'priority' => 'high', 'sort' => 'created_at', 'direction' => 'desc']));
    }
}
