<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketImport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

class TicketImportController extends Controller
{
    public function create(): View
    {
        return view('tickets.import');
    }

    public function history(): View
    {
        return view('tickets.import-history', [
            'imports' => TicketImport::query()->latest()->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'spreadsheet' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ], [
            'spreadsheet.mimes' => 'Unggah file CSV hasil ekspor spreadsheet.',
            'spreadsheet.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $rows = $this->readRows($validated['spreadsheet']->getRealPath());

        if ($rows === []) {
            return back()->withErrors(['spreadsheet' => 'File tidak berisi baris tiket yang dapat dibaca.']);
        }

        if (count($rows) > 5000) {
            return back()->withErrors(['spreadsheet' => 'Maksimal 5.000 baris tiket untuk sekali impor.']);
        }

        $engineers = User::query()->where('role', 'engineer')->get(['id', 'name'])
            ->keyBy(fn (User $engineer): string => mb_strtolower(trim($engineer->name)));
        $createdCount = 0;
        $skippedRows = [];
        $importedBy = $request->user();
        $originalFilename = mb_substr(basename($validated['spreadsheet']->getClientOriginalName()), 0, 255);

        DB::transaction(function () use ($rows, $engineers, $importedBy, $originalFilename, &$createdCount, &$skippedRows): void {
            $lockExists = DB::table('ticket_number_locks')->where('id', 1)->increment('lock_version');

            if (! $lockExists) {
                throw new RuntimeException('The ticket numbering lock is missing.');
            }

            $nextTicketNumber = Ticket::query()->count() + 1;

            foreach ($rows as $row) {
                $location = $this->cell($row, 'Lokasi');
                $description = $this->cell($row, 'Deskripsi issue');
                $reporter = $this->cell($row, 'PIC / Pelapor') ?: $this->cell($row, 'Pelapor / Unit') ?: 'Tidak diketahui';

                if ($location === '' || $description === '') {
                    $skippedRows[] = 'Baris '.$row['_line'].': lokasi atau deskripsi kosong.';

                    continue;
                }

                if ($this->isDuplicate($reporter, $location, $description)) {
                    $skippedRows[] = 'Baris '.$row['_line'].': tiket aktif serupa sudah ada.';

                    continue;
                }

                $technicianName = $this->cell($row, 'Teknisi SSH');
                $engineerName = trim(Str::before($technicianName, ','));
                $engineer = $technicianName !== '' ? $engineers->get(mb_strtolower($engineerName)) : null;
                $createdAt = $this->parseDate($this->cell($row, 'Tanggal Masuk')) ?? now();

                $ticket = Ticket::query()->create([
                    'ticket_number' => sprintf('TKT-%03d', $nextTicketNumber++),
                    'legacy_ticket_number' => $this->cell($row, 'Ticket ID') ?: null,
                    'report_source' => $this->cell($row, 'Asal Laporan') ?: null,
                    'location' => $location,
                    'project_name' => $this->cell($row, 'Project') ?: null,
                    'reporter_unit' => $this->cell($row, 'Pelapor / Unit') ?: null,
                    'reporter_name' => $this->cell($row, 'PIC / Pelapor') ?: $this->cell($row, 'Pelapor / Unit') ?: 'Tidak diketahui',
                    'coordinator_name' => $this->cell($row, 'Koordinator') ?: '-',
                    'issue_category' => $this->cell($row, 'Kategori Masalah') ?: null,
                    'outage_category' => $this->cell($row, 'Kategori Gangguan') ?: null,
                    'devices' => $this->cell($row, 'Device Issue') ?: null,
                    'description' => $description,
                    'pic_name' => $this->cell($row, 'PIC / Pelapor') ?: null,
                    'pic_contact' => $this->cell($row, 'Kontak PIC/ Vendor') ?: null,
                    'priority' => $this->mapPriority($this->cell($row, 'Prioritas')),
                    'delivery_status' => $this->cell($row, 'Deliver to Engineer') ?: null,
                    'engineer_id' => $engineer?->id,
                    'assigned_engineer_name' => in_array($technicianName, Ticket::RESPONSIBLE_ENGINEERS, true) ? $technicianName : null,
                    'technician_name' => $technicianName ?: null,
                    'status' => $this->mapStatus($this->cell($row, 'Status')),
                    'handling_notes' => $this->cell($row, 'Resume Kegiatan') ?: null,
                    'sla_target' => $this->cell($row, 'SLA (Jam/Hari Target)') ?: null,
                    'due_at' => $this->parseDate($this->cell($row, 'Deadline')),
                    'action_type' => $this->cell($row, 'Action') ?: null,
                    'team_plan' => $this->cell($row, 'Plan tim Enginner') ?: null,
                    'resolved_at' => $this->parseDate($this->cell($row, 'Tanggal Selesai')),
                    'duration_text' => $this->cell($row, 'Durasi') ?: null,
                ]);
                $ticket->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();

                foreach (['Tindakan 1', 'Tindakan 2'] as $actionColumn) {
                    $actionNotes = $this->cell($row, $actionColumn);

                    if ($actionNotes !== '') {
                        $activity = TicketActivity::query()->create([
                            'ticket_id' => $ticket->id,
                            'action' => 'handling_note',
                            'message' => $actionColumn.' dari spreadsheet.',
                            'notes' => $actionNotes,
                            'created_at' => $createdAt,
                            'updated_at' => $createdAt,
                        ]);
                        $activity->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
                    }
                }

                if (filled($ticket->handling_notes)) {
                    $activity = TicketActivity::query()->create([
                        'ticket_id' => $ticket->id,
                        'action' => 'handling_note',
                        'message' => 'Resume kegiatan dari spreadsheet.',
                        'notes' => $ticket->handling_notes,
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                    $activity->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
                }

                $createdCount++;
            }

            TicketImport::query()->create([
                'imported_by_id' => $importedBy->id,
                'imported_by_name' => $importedBy->name,
                'original_filename' => $originalFilename,
                'total_rows' => count($rows),
                'created_count' => $createdCount,
                'skipped_count' => count($skippedRows),
            ]);
        }, 5);

        return redirect()->route('tickets.index')->with('import_result', [
            'created' => $createdCount,
            'skipped' => count($skippedRows),
            'rows' => array_slice($skippedRows, 0, 15),
        ]);
    }

    /** @return array<int, array<string, string>> */
    private function readRows(string $path): array
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return [];
        }

        try {
            $headers = fgetcsv($handle, null, ',', '"', '\\');

            if ($headers === false) {
                return [];
            }

            $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $headers);
            $rows = [];
            $lineNumber = 1;

            while (($values = fgetcsv($handle, null, ',', '"', '\\')) !== false) {
                $lineNumber++;

                if (count(array_filter($values, fn (mixed $value): bool => trim((string) $value) !== '')) === 0) {
                    continue;
                }

                $row = [];

                foreach ($headers as $index => $header) {
                    if ($header !== '') {
                        $row[$header] = trim((string) ($values[$index] ?? ''));
                    }
                }

                $row['_line'] = (string) $lineNumber;
                $rows[] = $row;
            }

            return $rows;
        } finally {
            fclose($handle);
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return mb_strtolower(trim($header));
    }

    /** @param array<string, string> $row */
    private function cell(array $row, string $header): string
    {
        return trim($row[mb_strtolower($header)] ?? '');
    }

    private function parseDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        foreach (['!d/m/Y H:i:s', '!d/m/Y H:i', '!d/m/Y', '!Y-m-d H:i:s', '!Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date !== false) {
                    return $date;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    private function mapStatus(string $status): string
    {
        return match (mb_strtolower(trim($status))) {
            'pending' => 'pending',
            'in progress', 'in_progress', 'progress' => 'in_progress',
            'resolved', 'selesai' => 'resolved',
            'closed', 'close' => 'closed',
            default => 'open',
        };
    }

    private function mapPriority(string $priority): string
    {
        return match (mb_strtolower(trim($priority))) {
            'low', 'minor', 'rendah' => 'low',
            'high', 'mayor', 'major' => 'high',
            'critical', 'urgent', 'kritis' => 'urgent',
            default => 'normal',
        };
    }

    private function isDuplicate(string $reporter, string $location, string $description): bool
    {
        $activeDescriptions = Ticket::query()
            ->whereIn('status', Ticket::ACTIVE_STATUSES)
            ->whereRaw('LOWER(reporter_name) = LOWER(?)', [$reporter])
            ->whereRaw('LOWER(location) = LOWER(?)', [$location])
            ->pluck('description');
        $candidate = preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($description))) ?? '';

        foreach ($activeDescriptions as $activeDescription) {
            $existing = preg_replace('/[^\pL\pN]+/u', '', mb_strtolower(trim($activeDescription))) ?? '';

            if ($candidate === $existing) {
                return true;
            }

            similar_text($candidate, $existing, $similarity);

            if ($similarity >= 82) {
                return true;
            }
        }

        return false;
    }
}
