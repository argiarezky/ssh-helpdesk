<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketImport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TicketImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_engineer_can_view_spreadsheet_import_fields(): void
    {
        $engineer = User::factory()->engineer()->create();

        $this->actingAs($engineer)->get(route('tickets.import.create'))
            ->assertOk()
            ->assertSee('Impor dari spreadsheet')
            ->assertSee('Tanggal Masuk')
            ->assertSee('Plan tim Engineer')
            ->assertSee('Ticket ID lama disimpan sebagai referensi');
    }

    public function test_engineer_imports_spreadsheet_columns_and_keeps_legacy_ticket_number(): void
    {
        $engineer = User::factory()->engineer()->create(['name' => 'Nalar']);
        Notification::fake();
        $csv = $this->spreadsheetCsv([
            ['TKT-777', '20/10/2026 11:14', 'WhatsApp', 'BKN Mataram', 'BAKTI KOMDIGI', 'Koordinator', 'Ragil', 'Preventive Maintenance', '', 'Router, Modem', 'Router perlu pengecekan koneksi.', 'Faris', '081234567890', 'Mayor', 'Done', 'Nalar', 'Restart router', '', '3 Jam', '21/10/2026 15:00', 'Remote', 'Pandu PIC', 'Closed', '21/10/2026 15:30', '28 jam 16 menit', 'Router kembali normal'],
        ]);

        $this->actingAs($engineer)->post(route('tickets.import.store'), [
            'spreadsheet' => UploadedFile::fake()->createWithContent('tickets.csv', $csv),
        ])->assertRedirect(route('tickets.index'))
            ->assertSessionHas('import_result.created', 1)
            ->assertSessionHas('import_result.skipped', 0);

        $ticket = Ticket::query()->sole();

        $this->assertSame('TKT-001', $ticket->ticket_number);
        $this->assertSame('TKT-777', $ticket->legacy_ticket_number);
        $this->assertSame('BKN Mataram', $ticket->location);
        $this->assertSame('BAKTI KOMDIGI', $ticket->project_name);
        $this->assertSame('high', $ticket->priority);
        $this->assertSame('closed', $ticket->status);
        $this->assertSame($engineer->id, $ticket->engineer_id);
        $this->assertSame('28 jam 16 menit', $ticket->duration);
        $this->assertSame('Router kembali normal', $ticket->handling_notes);
        $this->assertSame(2, TicketActivity::query()->where('ticket_id', $ticket->id)->count());
        $import = TicketImport::query()->sole();
        $this->assertSame('tickets.csv', $import->original_filename);
        $this->assertSame('Nalar', $import->imported_by_name);
        $this->assertSame(1, $import->total_rows);
        $this->assertSame(1, $import->created_count);
        $this->assertSame(0, $import->skipped_count);
        Notification::assertNothingSent();

        $this->get(route('tickets.import.history'))
            ->assertOk()
            ->assertSee('Riwayat impor CSV')
            ->assertSee('tickets.csv')
            ->assertSee('Nalar');
    }

    public function test_import_skips_blank_and_duplicate_active_rows(): void
    {
        $engineer = User::factory()->engineer()->create();
        $csv = $this->spreadsheetCsv([
            ['TKT-001', '20/10/2026 11:14', 'WhatsApp', 'Gedung A', 'Project A', 'Unit A', 'Ragil', 'Gangguan', '', 'Router', 'Router tidak merespons.', 'Faris', '', 'Critical', 'Done', '', '', '', '', '', 'Remote', '', 'Open', '', '', ''],
            ['TKT-002', '20/10/2026 12:14', 'WhatsApp', 'Gedung A', 'Project A', 'Unit A', 'Ragil', 'Gangguan', '', 'Router', 'Router tidak merespons!', 'Faris', '', 'Critical', 'Done', '', '', '', '', '', 'Remote', '', 'Open', '', '', ''],
            ['TKT-003', '', '', 'Lokasi tanpa deskripsi', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]);

        $this->actingAs($engineer)->post(route('tickets.import.store'), [
            'spreadsheet' => UploadedFile::fake()->createWithContent('tickets.csv', $csv),
        ])->assertRedirect(route('tickets.index'))
            ->assertSessionHas('import_result.created', 1)
            ->assertSessionHas('import_result.skipped', 2);

        $this->assertDatabaseCount('tickets', 1);
        $this->assertDatabaseHas('tickets', ['legacy_ticket_number' => 'TKT-001']);
        $this->assertDatabaseHas('ticket_imports', ['total_rows' => 3, 'created_count' => 1, 'skipped_count' => 2]);
    }

    public function test_guest_cannot_import_spreadsheet_tickets(): void
    {
        $this->post(route('tickets.import.store'), [
            'spreadsheet' => UploadedFile::fake()->createWithContent('tickets.csv', $this->spreadsheetCsv([])),
        ])->assertRedirect(route('login'));

        $this->assertDatabaseCount('tickets', 0);
    }

    /** @param array<int, array<int, string>> $rows */
    private function spreadsheetCsv(array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['Ticket ID', 'Tanggal Masuk', 'Asal Laporan', 'Lokasi', 'Project', 'Pelapor / Unit', 'Koordinator', 'Kategori Masalah', 'Kategori Gangguan', 'Device Issue', 'Deskripsi issue', 'PIC / Pelapor', 'Kontak PIC/ Vendor', 'Prioritas', 'Deliver to Engineer', 'Teknisi SSH', 'Tindakan 1', 'Tindakan 2', 'SLA (Jam/Hari Target)', 'Deadline', 'Action', 'Plan tim Enginner', 'Status', 'Tanggal Selesai', 'Durasi', 'Resume Kegiatan']);

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}
