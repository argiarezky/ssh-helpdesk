<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\ResponseTemplate;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\TicketAttachment;
use App\Models\User;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if ($request->boolean('reset')) {
            $request->session()->forget('ticket_filters');
        } elseif ($request->query->count() === 0 && $request->session()->has('ticket_filters')) {
            return redirect()->route('tickets.index', $request->session()->get('ticket_filters'));
        }

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,pending,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'engineer_id' => ['nullable', 'integer'],
            'responsible_engineer' => ['nullable', Rule::in(Ticket::RESPONSIBLE_ENGINEERS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:created_at,updated_at,ticket_number,priority,status,reporter_name,engineer'],
            'direction' => ['nullable', 'in:asc,desc'],
            'archived' => ['nullable', 'in:1'],
        ]);
        $filters = $this->normalizeFilters($request->query->all());

        if ($filters['archived'] === '' && preg_match('/^TKT-\d+$/i', $filters['search']) === 1) {
            $exactTicket = Ticket::query()->whereNull('archived_at')->where('ticket_number', strtoupper($filters['search']))->first();

            if ($exactTicket !== null) {
                return redirect()->route('tickets.show', $exactTicket);
            }
        }

        $request->session()->put('ticket_filters', array_filter($filters, fn (mixed $value): bool => $value !== '' && $value !== null));

        $tickets = $this->filteredTicketQuery($filters)->with('engineer')->paginate(10)->withQueryString();

        return view('tickets.index', compact('tickets', 'filters'));
    }

    public function create(): View
    {
        return view('tickets.create', [
            'statuses' => ['open', 'pending', 'in_progress', 'resolved', 'closed'],
            'priorities' => ['low', 'normal', 'high', 'urgent'],
            'projects' => Ticket::query()
                ->whereNotNull('project_name')
                ->where('project_name', '!=', '')
                ->distinct()
                ->orderBy('project_name')
                ->pluck('project_name'),
            'issueCategories' => Ticket::query()
                ->whereNotNull('issue_category')
                ->where('issue_category', '!=', '')
                ->distinct()
                ->orderBy('issue_category')
                ->pluck('issue_category'),
            'responseTemplates' => ResponseTemplate::query()->orderBy('title')->get(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFiles = $validated['attachments'] ?? [];
        unset($validated['attachments']);
        if (filled($validated['assigned_engineer_name'] ?? null)) {
            $validated['engineer_id'] = User::query()->where('role', 'engineer')
                ->where('name', $validated['assigned_engineer_name'])->value('id');
        }
        $createdAt = isset($validated['created_at']) ? Carbon::parse($validated['created_at']) : now();
        unset($validated['created_at']);

        $ticket = DB::transaction(function () use ($validated, $createdAt): Ticket {
            $this->lockTicketNumbering();

            $ticket = Ticket::query()->create([
                ...$validated,
                'ticket_number' => sprintf('TKT-%03d', Ticket::query()->count() + 1),
            ]);

            $ticket->forceFill(['created_at' => $createdAt])->save();

            return $ticket;
        }, 5);

        $this->storeAttachments($ticket, $uploadedFiles, $request->user());
        $this->recordActivity($ticket, 'created', 'Tiket dibuat.');
        if (filled($ticket->handling_notes)) {
            $this->recordActivity($ticket, 'handling_note', 'Catatan penanganan awal ditambahkan.', $ticket->handling_notes);
        }
        ($ticket->engineer ?? $request->user())->notify(new TicketCreatedNotification($ticket));

        return redirect()->route('dashboard')->with('ticket_feedback', 'Ticket berhasil dibuat.');
    }

    public function show(Ticket $ticket): View
    {
        $ticket->load(['engineer', 'activities.user', 'attachments.uploader']);

        return view('tickets.show', compact('ticket'));
    }

    public function edit(Ticket $ticket): View
    {
        return view('tickets.edit', [
            'ticket' => $ticket,
            'statuses' => ['open', 'pending', 'in_progress', 'resolved', 'closed'],
            'priorities' => ['low', 'normal', 'high', 'urgent'],
            'projects' => Ticket::query()
                ->whereNotNull('project_name')
                ->where('project_name', '!=', '')
                ->distinct()
                ->orderBy('project_name')
                ->pluck('project_name'),
            'issueCategories' => Ticket::query()
                ->whereNotNull('issue_category')
                ->where('issue_category', '!=', '')
                ->distinct()
                ->orderBy('issue_category')
                ->pluck('issue_category'),
            'responseTemplates' => ResponseTemplate::query()->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $validated = $request->validated();
        $uploadedFiles = $validated['attachments'] ?? [];
        unset($validated['attachments']);
        $previousStatus = $ticket->status;
        $previousNotes = $ticket->handling_notes;
        $previousResponsibleEngineer = $ticket->assigned_engineer_name;

        if (filled($validated['assigned_engineer_name'] ?? null)) {
            $validated['engineer_id'] = User::query()->where('role', 'engineer')
                ->where('name', $validated['assigned_engineer_name'])->value('id');
        }

        if (
            $validated['status'] !== 'pending'
            || $ticket->status !== 'pending'
            || (int) $ticket->engineer_id !== (int) ($validated['engineer_id'] ?? 0)
            || $previousResponsibleEngineer !== ($validated['assigned_engineer_name'] ?? null)
        ) {
            $validated['pending_reminded_at'] = null;
        }

        $ticket->fill($validated);

        if ($ticket->isDirty('due_at') || $ticket->isDirty('engineer_id') || $ticket->isDirty('assigned_engineer_name')) {
            $ticket->due_reminded_at = null;
        }

        $changedFields = array_values(array_diff(array_keys($ticket->getDirty()), ['status', 'handling_notes', 'pending_reminded_at', 'due_reminded_at']));
        $fieldLabels = [
            'reporter_name' => 'pelapor',
            'location' => 'lokasi',
            'coordinator_name' => 'koordinator',
            'description' => 'deskripsi',
            'report_source' => 'asal laporan',
            'project_name' => 'project',
            'reporter_unit' => 'pelapor/unit',
            'issue_category' => 'kategori masalah',
            'outage_category' => 'kategori gangguan',
            'devices' => 'perangkat',
            'pic_name' => 'PIC',
            'pic_contact' => 'kontak PIC',
            'delivery_status' => 'status pengiriman engineer',
            'technician_name' => 'teknisi SSH',
            'assigned_engineer_name' => 'engineer penanggung jawab',
            'sla_target' => 'target SLA',
            'action_type' => 'jenis tindakan',
            'team_plan' => 'rencana tim engineer',
            'resolved_at' => 'tanggal selesai',
            'engineer_id' => 'engineer',
            'priority' => 'prioritas',
            'due_at' => 'tenggat',
        ];
        $changedFields = array_map(fn (string $field): string => $fieldLabels[$field] ?? $field, $changedFields);
        $ticket->save();
        $ticket->unsetRelation('engineer');
        $this->storeAttachments($ticket, $uploadedFiles, $request->user());

        if ($changedFields !== []) {
            $this->recordActivity($ticket, 'updated', 'Detail tiket diperbarui: '.implode(', ', $changedFields).'.');
        }

        if ($previousStatus !== $ticket->status) {
            $this->recordActivity($ticket, 'status_changed', 'Status diubah dari '.$previousStatus.' menjadi '.$ticket->status.'.');
        }

        if ($previousNotes !== $ticket->handling_notes && filled($ticket->handling_notes)) {
            $this->recordActivity($ticket, 'handling_note', 'Catatan penanganan ditambahkan.', $ticket->handling_notes);
        }

        if ($previousResponsibleEngineer !== $ticket->assigned_engineer_name && filled($ticket->assigned_engineer_name)) {
            $this->recordActivity($ticket, 'assigned', 'Engineer penanggung jawab diubah menjadi '.$ticket->assigned_engineer_name.'.');
        }

        if ($previousStatus !== $ticket->status) {
            $ticket->engineer?->notify(new TicketStatusChangedNotification($ticket, $previousStatus));
        }

        return redirect()->route('tickets.show', $ticket)->with('ticket_feedback', 'Perubahan tiket berhasil disimpan.');
    }

    public function downloadAttachment(Ticket $ticket, TicketAttachment $attachment): StreamedResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);
        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name, [
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroyAttachment(Ticket $ticket, TicketAttachment $attachment): RedirectResponse
    {
        abort_unless($attachment->ticket_id === $ticket->id, 404);

        Storage::disk($attachment->disk)->delete($attachment->path);
        $this->recordActivity($ticket, 'attachment_deleted', 'Lampiran dihapus: '.$attachment->original_name.'.');
        $attachment->delete();

        return back()->with('success', 'Lampiran berhasil dihapus.');
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        DB::transaction(function () use ($ticket): void {
            $this->lockTicketNumbering();
            $ticket->delete();
            $this->resequenceTicketNumbers();
        }, 5);

        return redirect()->route('tickets.index')->with('ticket_feedback', 'Ticket berhasil dihapus.');
    }

    public function archive(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['archived_at' => now()]);
        $this->recordActivity($ticket, 'archived', 'Tiket diarsipkan.');

        return redirect()->route('tickets.index')->with('success', $ticket->ticket_number.' berhasil diarsipkan.');
    }

    public function restore(Ticket $ticket): RedirectResponse
    {
        $ticket->update(['archived_at' => null]);
        $this->recordActivity($ticket, 'restored', 'Tiket dipulihkan dari arsip.');

        return redirect()->route('tickets.show', $ticket)->with('success', $ticket->ticket_number.' berhasil dipulihkan.');
    }

    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        $claimed = Ticket::query()->whereKey($ticket->id)->whereNull('engineer_id')->whereNull('assigned_engineer_name')->whereNull('archived_at')
            ->update(['engineer_id' => $request->user()->id, 'updated_at' => now()]);

        if ($claimed) {
            $this->recordActivity($ticket, 'claimed', 'Tiket diambil oleh '.$request->user()->name.'.');
        }

        return back()->with($claimed ? 'success' : 'status', $claimed ? 'Tiket berhasil diambil.' : 'Tiket sudah ditugaskan ke engineer lain.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:update,delete,archive,restore'],
            'select_all_matching' => ['nullable', 'boolean'],
            'ticket_ids' => ['nullable', 'array', 'max:1000'],
            'ticket_ids.*' => ['required', 'integer', 'distinct', 'exists:tickets,id'],
            'excluded_ticket_ids' => ['nullable', 'array'],
            'excluded_ticket_ids.*' => ['required', 'integer', 'distinct', 'exists:tickets,id'],
            'status' => ['nullable', 'required_if:action,update', 'in:open,pending,in_progress,resolved,closed'],
            'filters' => ['nullable', 'array'],
            'filters.*' => ['nullable', 'string', 'max:255'],
            'filters.search' => ['nullable', 'string', 'max:255'],
            'filters.status' => ['nullable', 'in:open,pending,in_progress,resolved,closed'],
            'filters.priority' => ['nullable', 'in:low,normal,high,urgent'],
            'filters.engineer_id' => ['nullable', 'integer'],
            'filters.responsible_engineer' => ['nullable', Rule::in(Ticket::RESPONSIBLE_ENGINEERS)],
            'filters.from' => ['nullable', 'date_format:Y-m-d'],
            'filters.to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:filters.from'],
            'filters.sort' => ['nullable', 'in:created_at,updated_at,ticket_number,priority,status,reporter_name,engineer'],
            'filters.direction' => ['nullable', 'in:asc,desc'],
            'filters.archived' => ['nullable', 'in:1'],
        ]);

        if (! ($validated['select_all_matching'] ?? false) && empty($validated['ticket_ids'])) {
            return back()->withErrors(['ticket_ids' => 'Pilih minimal satu tiket.']);
        }

        $action = $validated['action'];
        $filters = $this->normalizeFilters($validated['filters'] ?? []);
        $affectedCount = DB::transaction(function () use ($action, $validated, $filters): int {
            $query = ($validated['select_all_matching'] ?? false)
                ? $this->filteredTicketQuery($filters)
                : Ticket::query()->whereIn('id', $validated['ticket_ids']);
            if (($validated['select_all_matching'] ?? false) && ! empty($validated['excluded_ticket_ids'])) {
                $query->whereNotIn('id', $validated['excluded_ticket_ids']);
            }
            $tickets = $query->with('engineer')->get();

            if ($action === 'update') {
                foreach ($tickets as $ticket) {
                    $previousStatus = $ticket->status;
                    $attributes = ['status' => $validated['status']];

                    if ($validated['status'] !== 'pending' || $ticket->status !== 'pending') {
                        $attributes['pending_reminded_at'] = null;
                    }

                    $ticket->update($attributes);

                    if ($previousStatus !== $ticket->status) {
                        $this->recordActivity($ticket, 'status_changed', 'Status diubah dari '.$previousStatus.' menjadi '.$ticket->status.'.');
                        $ticket->engineer?->notify(new TicketStatusChangedNotification($ticket, $previousStatus));
                    }
                }

                return $tickets->count();
            }

            if (in_array($action, ['archive', 'restore'], true)) {
                foreach ($tickets as $ticket) {
                    $isArchived = $action === 'archive';
                    $ticket->update(['archived_at' => $isArchived ? now() : null]);
                    $this->recordActivity($ticket, $isArchived ? 'archived' : 'restored', $isArchived ? 'Tiket diarsipkan.' : 'Tiket dipulihkan dari arsip.');
                }

                return $tickets->count();
            }

            $this->lockTicketNumbering();

            foreach ($tickets as $ticket) {
                $ticket->delete();
            }

            $this->resequenceTicketNumbers();

            return $tickets->count();
        }, 5);

        $message = match ($action) {
            'update' => $affectedCount.' tiket berhasil diperbarui.',
            'archive' => $affectedCount.' tiket berhasil diarsipkan.',
            'restore' => $affectedCount.' tiket berhasil dipulihkan.',
            default => $affectedCount.' tiket berhasil dihapus.',
        };

        return back()->with($action === 'delete' ? 'ticket_feedback' : 'success', $message);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,pending,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'engineer_id' => ['nullable', 'integer'],
            'responsible_engineer' => ['nullable', Rule::in(Ticket::RESPONSIBLE_ENGINEERS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:created_at,updated_at,ticket_number,priority,status,reporter_name,engineer'],
            'direction' => ['nullable', 'in:asc,desc'],
            'archived' => ['nullable', 'in:1'],
        ]);
        $filters = $this->normalizeFilters($request->query->all());
        $tickets = $this->filteredTicketQuery($filters)->with(['engineer', 'activities']);

        return response()->streamDownload(function () use ($tickets): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Nomor Tiket', 'Nomor Spreadsheet', 'Tanggal Masuk', 'Asal Laporan', 'Lokasi', 'Project', 'Pelapor / Unit', 'Koordinator', 'Kategori Masalah', 'Kategori Gangguan', 'Device Issue', 'Deskripsi Issue', 'PIC / Pelapor', 'Kontak PIC / Vendor', 'Prioritas', 'Deliver to Engineer', 'Engineer Penanggung Jawab', 'Teknisi SSH', 'Engineer Akun', 'Tindakan 1-2 / Riwayat', 'SLA Target', 'Deadline', 'Action', 'Plan Tim Engineer', 'Status', 'Tanggal Selesai', 'Durasi', 'Resume Kegiatan'], ',', '"', '\\', "\r\n");

            $tickets->chunk(500, function (EloquentCollection $ticketChunk) use ($output): void {
                foreach ($ticketChunk as $ticket) {
                    $values = [
                        $ticket->ticket_number,
                        $ticket->legacy_ticket_number,
                        $ticket->created_at?->format('d-m-Y H:i'),
                        $ticket->report_source,
                        $ticket->location,
                        $ticket->project_name,
                        $ticket->reporter_unit,
                        $ticket->coordinator_name,
                        $ticket->issue_category,
                        $ticket->outage_category,
                        $ticket->devices,
                        $ticket->description,
                        $ticket->pic_name,
                        $ticket->pic_contact,
                        ['normal' => 'Medium', 'urgent' => 'Critical'][$ticket->priority] ?? ucfirst($ticket->priority),
                        $ticket->delivery_status,
                        $ticket->assigned_engineer_name,
                        $ticket->technician_name,
                        $ticket->engineer?->name,
                        $ticket->activities->where('action', 'handling_note')->pluck('notes')->filter()->implode("\n"),
                        $ticket->sla_target,
                        $ticket->due_at?->format('d-m-Y H:i'),
                        $ticket->action_type,
                        $ticket->team_plan,
                        ['in_progress' => 'In Progress'][$ticket->status] ?? ucfirst($ticket->status),
                        $ticket->resolved_at?->format('d-m-Y H:i'),
                        $ticket->duration,
                        $ticket->handling_notes,
                    ];
                    $values = array_map(fn (?string $value): ?string => is_string($value) && preg_match('/^[=+@\-]/', $value) ? "'{$value}" : $value, $values);
                    fputcsv($output, $values, ',', '"', '\\', "\r\n");
                }
            });

            fclose($output);
        }, 'laporan-tiket-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:open,pending,in_progress,resolved,closed'],
            'priority' => ['nullable', 'in:low,normal,high,urgent'],
            'engineer_id' => ['nullable', 'integer'],
            'responsible_engineer' => ['nullable', Rule::in(Ticket::RESPONSIBLE_ENGINEERS)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'sort' => ['nullable', 'in:created_at,updated_at,ticket_number,priority,status,reporter_name,engineer'],
            'direction' => ['nullable', 'in:asc,desc'],
            'archived' => ['nullable', 'in:1'],
        ]);
        $filters = $this->normalizeFilters($request->query->all());
        $tickets = $this->filteredTicketQuery($filters)->with(['engineer', 'activities']);

        return response()->streamDownload(function () use ($tickets, $filters): void {
            $sheetPath = tempnam(sys_get_temp_dir(), 'ssh-helpdesk-sheet-');
            $workbookPath = tempnam(sys_get_temp_dir(), 'ssh-helpdesk-workbook-');

            if ($sheetPath === false || $workbookPath === false) {
                throw new RuntimeException('Unable to prepare the Excel export.');
            }

            try {
                $sheet = fopen($sheetPath, 'wb');

                if ($sheet === false) {
                    throw new RuntimeException('Unable to write the Excel worksheet.');
                }

                $headings = ['Nomor Tiket', 'Nomor Spreadsheet', 'Tanggal Masuk', 'Asal Laporan', 'Lokasi', 'Project', 'Pelapor / Unit', 'Koordinator', 'Kategori Masalah', 'Kategori Gangguan', 'Device Issue', 'Deskripsi Issue', 'PIC / Pelapor', 'Kontak PIC / Vendor', 'Prioritas', 'Deliver to Engineer', 'Engineer Penanggung Jawab', 'Teknisi SSH', 'Engineer Akun', 'Tindakan 1-2 / Riwayat', 'SLA Target', 'Deadline', 'Action', 'Plan Tim Engineer', 'Status', 'Tanggal Selesai', 'Durasi', 'Resume Kegiatan'];
                $lastColumn = $this->spreadsheetColumnName(count($headings));
                $ticketCount = (clone $tickets)->count();

                fwrite($sheet, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><dimension ref="A1:'.$lastColumn.($ticketCount + 8).'"/><sheetViews><sheetView workbookViewId="0"><pane ySplit="8" topLeftCell="A9" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetFormatPr defaultRowHeight="22"/><cols>');

                foreach ([16, 20, 20, 22, 24, 24, 24, 18, 24, 24, 28, 42, 24, 25, 16, 24, 28, 22, 22, 40, 18, 20, 20, 32, 18, 20, 18, 42] as $index => $width) {
                    $column = $index + 1;
                    fwrite($sheet, '<col min="'.$column.'" max="'.$column.'" width="'.$width.'" customWidth="1"/>');
                }

                fwrite($sheet, '</cols><sheetData>');
                $this->writeSpreadsheetRow($sheet, 1, ['SSH HelpDesk · Network Operations Center'], 1);
                $this->writeSpreadsheetRow($sheet, 2, ['Laporan Tiket'], 2);
                $this->writeSpreadsheetRow($sheet, 3, ['Ringkasan data tiket untuk kebutuhan operasional dan dokumentasi.'], 3);
                $this->writeSpreadsheetRow($sheet, 5, ['Tanggal ekspor', now()->format('d-m-Y H:i'), 'Jumlah tiket', (string) $ticketCount], [4, 5, 4, 5]);
                $this->writeSpreadsheetRow($sheet, 6, ['Dari tanggal', $filters['from'] ?: 'Semua tanggal', 'Sampai tanggal', $filters['to'] ?: 'Sekarang'], [4, 5, 4, 5]);
                $this->writeSpreadsheetRow($sheet, 8, $headings, 6);

                $rowNumber = 8;
                $tickets->chunk(500, function (EloquentCollection $ticketChunk) use ($sheet, &$rowNumber): void {

                    foreach ($ticketChunk as $ticket) {
                        $rowNumber++;
                        $values = [
                            $ticket->ticket_number,
                            $ticket->legacy_ticket_number,
                            $ticket->created_at?->format('d-m-Y H:i'),
                            $ticket->report_source,
                            $ticket->location,
                            $ticket->project_name,
                            $ticket->reporter_unit,
                            $ticket->coordinator_name,
                            $ticket->issue_category,
                            $ticket->outage_category,
                            $ticket->devices,
                            $ticket->description,
                            $ticket->pic_name,
                            $ticket->pic_contact,
                            ['normal' => 'Medium', 'urgent' => 'Critical'][$ticket->priority] ?? ucfirst($ticket->priority),
                            $ticket->delivery_status,
                            $ticket->assigned_engineer_name,
                            $ticket->technician_name,
                            $ticket->engineer?->name,
                            $ticket->activities->where('action', 'handling_note')->pluck('notes')->filter()->implode("\n"),
                            $ticket->sla_target,
                            $ticket->due_at?->format('d-m-Y H:i'),
                            $ticket->action_type,
                            $ticket->team_plan,
                            ['in_progress' => 'In Progress'][$ticket->status] ?? ucfirst($ticket->status),
                            $ticket->resolved_at?->format('d-m-Y H:i'),
                            $ticket->duration,
                            $ticket->handling_notes,
                        ];

                        $this->writeSpreadsheetRow($sheet, $rowNumber, $values, $rowNumber % 2 === 0 ? 7 : 8);
                    }
                });

                $lastRow = max(8, $ticketCount + 8);
                fwrite($sheet, '</sheetData><autoFilter ref="A8:'.$lastColumn.$lastRow.'"/><mergeCells count="3"><mergeCell ref="A1:'.$lastColumn.'1"/><mergeCell ref="A2:'.$lastColumn.'2"/><mergeCell ref="A3:'.$lastColumn.'3"/></mergeCells><pageMargins left="0.25" right="0.25" top="0.5" bottom="0.5" header="0.2" footer="0.2"/></worksheet>');
                fclose($sheet);

                $archive = new \ZipArchive;

                if ($archive->open($workbookPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                    throw new RuntimeException('Unable to create the Excel workbook.');
                }

                $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
                $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
                $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Laporan Tiket" sheetId="1" r:id="rId1"/></sheets></workbook>');
                $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
                $archive->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="4"><font><sz val="10"/><name val="Calibri"/></font><font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font><font><b/><sz val="20"/><color rgb="FF17324B"/><name val="Calibri"/></font><font><sz val="10"/><i/><color rgb="FF64748B"/><name val="Calibri"/></font></fonts><fills count="4"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF17324B"/><bgColor indexed="64"/></patternFill></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEAF0F5"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD8E0E7"/></left><right style="thin"><color rgb="FFD8E0E7"/></right><top style="thin"><color rgb="FFD8E0E7"/></top><bottom style="thin"><color rgb="FFD8E0E7"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="9"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/><xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="3" fillId="0" borderId="0" xfId="0" applyFont="1"/><xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1"/><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFill="1" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>');
                $archive->addFile($sheetPath, 'xl/worksheets/sheet1.xml');
                $archive->close();

                readfile($workbookPath);
            } finally {
                @unlink($sheetPath);
                @unlink($workbookPath);
            }
        }, 'laporan-tiket-'.now()->format('Ymd-His').'.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    /**
     * @param  resource  $output
     * @param  array<int, int>|int  $styles
     * @param  array<int, scalar|null>  $values
     */
    private function writeSpreadsheetRow($output, int $rowNumber, array $values, int|array $styles): void
    {
        fwrite($output, '<row r="'.$rowNumber.'">');

        foreach ($values as $index => $value) {
            $style = is_array($styles) ? ($styles[$index] ?? 0) : $styles;
            $cellReference = $this->spreadsheetColumnName($index + 1).$rowNumber;
            $safeValue = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', (string) ($value ?? '')) ?? '';
            $escapedValue = htmlspecialchars($safeValue, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            fwrite($output, '<c r="'.$cellReference.'" t="inlineStr"'.($style > 0 ? ' s="'.$style.'"' : '').'><is><t xml:space="preserve">'.$escapedValue.'</t></is></c>');
        }

        fwrite($output, '</row>');
    }

    private function spreadsheetColumnName(int $columnNumber): string
    {
        $columnName = '';

        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $columnName = chr(65 + $remainder).$columnName;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $columnName;
    }

    private function normalizeFilters(array $filters): array
    {
        return [
            'search' => trim((string) ($filters['search'] ?? '')),
            'status' => in_array($filters['status'] ?? '', ['open', 'pending', 'in_progress', 'resolved', 'closed'], true) ? $filters['status'] : '',
            'priority' => in_array($filters['priority'] ?? '', ['low', 'normal', 'high', 'urgent'], true) ? $filters['priority'] : '',
            'engineer_id' => ctype_digit((string) ($filters['engineer_id'] ?? '')) ? (string) $filters['engineer_id'] : '',
            'responsible_engineer' => in_array($filters['responsible_engineer'] ?? '', Ticket::RESPONSIBLE_ENGINEERS, true) ? $filters['responsible_engineer'] : '',
            'from' => (string) ($filters['from'] ?? ''),
            'to' => (string) ($filters['to'] ?? ''),
            'sort' => in_array($filters['sort'] ?? '', ['created_at', 'updated_at', 'ticket_number', 'priority', 'status', 'reporter_name', 'engineer'], true) ? $filters['sort'] : 'created_at',
            'direction' => ($filters['direction'] ?? '') === 'asc' ? 'asc' : 'desc',
            'archived' => ($filters['archived'] ?? '') === '1' ? '1' : '',
        ];
    }

    private function filteredTicketQuery(array $filters): Builder
    {
        $query = Ticket::query();

        if ($filters['archived'] === '1') {
            $query->whereNotNull('archived_at');
        } else {
            $query->whereNull('archived_at');
        }

        if ($filters['search'] !== '') {
            $search = $filters['search'];
            $query->where(fn (Builder $query) => $query->where('ticket_number', $search)
                ->orWhere('ticket_number', 'like', "%{$search}%")
                ->orWhere('legacy_ticket_number', 'like', "%{$search}%")
                ->orWhere('reporter_name', 'like', "%{$search}%")
                ->orWhere('reporter_unit', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('project_name', 'like', "%{$search}%")
                ->orWhere('assigned_engineer_name', 'like', "%{$search}%")
                ->orWhere('pic_name', 'like', "%{$search}%")
                ->orWhere('pic_contact', 'like', "%{$search}%")
                ->orWhere('devices', 'like', "%{$search}%")
                ->orWhere('issue_category', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        foreach (['status', 'priority'] as $column) {
            if ($filters[$column] !== '') {
                $query->where($column, $filters[$column]);
            }
        }

        if ($filters['engineer_id'] !== '') {
            $query->where('engineer_id', $filters['engineer_id']);
        }

        if ($filters['responsible_engineer'] !== '') {
            $responsibleEngineer = $filters['responsible_engineer'];
            $query->where(fn (Builder $query) => $query->where('assigned_engineer_name', $responsibleEngineer)
                ->orWhereHas('engineer', fn (Builder $engineerQuery) => $engineerQuery->where('name', $responsibleEngineer)));
        }

        if ($filters['from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['from']);
        }

        if ($filters['to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['to']);
        }

        if ($filters['sort'] === 'engineer') {
            $query->orderByRaw('COALESCE(assigned_engineer_name, (SELECT name FROM users WHERE users.id = tickets.engineer_id)) '.$filters['direction']);
        } else {
            $query->orderBy($filters['sort'], $filters['direction']);
        }

        return $query->orderByDesc('id');
    }

    private function recordActivity(Ticket $ticket, string $action, string $message, ?string $notes = null): void
    {
        TicketActivity::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'message' => $message,
            'notes' => $notes,
        ]);
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function storeAttachments(Ticket $ticket, array $files, User $uploader): void
    {
        if ($files === []) {
            return;
        }

        $storedPaths = [];

        try {
            DB::transaction(function () use ($ticket, $files, $uploader, &$storedPaths): void {
                foreach ($files as $file) {
                    $path = $file->store('ticket-attachments/'.$ticket->id, 'local');

                    if (! is_string($path)) {
                        throw new RuntimeException('Lampiran gagal disimpan.');
                    }

                    $storedPaths[] = $path;
                    $attachment = $ticket->attachments()->create([
                        'uploaded_by' => $uploader->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                        'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
                        'size' => $file->getSize(),
                    ]);
                    $this->recordActivity($ticket, 'attachment_added', 'Lampiran ditambahkan: '.$attachment->original_name.'.');
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    private function lockTicketNumbering(): void
    {
        $locked = DB::table('ticket_number_locks')
            ->where('id', 1)
            ->increment('lock_version');

        if (! $locked) {
            throw new RuntimeException('The ticket numbering lock is missing.');
        }
    }

    private function resequenceTicketNumbers(): void
    {
        $ticketIds = Ticket::query()->orderBy('id')->pluck('id')->all();

        if ($ticketIds === []) {
            return;
        }

        do {
            $temporaryPrefix = '__TKT_RESEQUENCE_'.Str::uuid().'__';
        } while (Ticket::query()->where('ticket_number', 'like', $temporaryPrefix.'%')->exists());

        $temporaryNumbers = [];

        foreach ($ticketIds as $ticketId) {
            $temporaryNumbers[$ticketId] = $temporaryPrefix.$ticketId;
        }

        $this->updateTicketNumbers($temporaryNumbers);

        $ticketNumbers = [];

        foreach ($ticketIds as $index => $ticketId) {
            $ticketNumbers[$ticketId] = sprintf('TKT-%03d', $index + 1);
        }

        $this->updateTicketNumbers($ticketNumbers);
    }

    /**
     * @param  array<int, string>  $ticketNumbers
     */
    private function updateTicketNumbers(array $ticketNumbers): void
    {
        foreach (array_chunk($ticketNumbers, 300, true) as $ticketNumberChunk) {
            $cases = [];
            $bindings = [];

            foreach ($ticketNumberChunk as $ticketId => $ticketNumber) {
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $ticketId;
                $bindings[] = $ticketNumber;
            }

            $idPlaceholders = implode(', ', array_fill(0, count($ticketNumberChunk), '?'));
            $bindings = [...$bindings, ...array_keys($ticketNumberChunk)];

            DB::update(
                'UPDATE tickets SET ticket_number = CASE id '.implode(' ', $cases).' END WHERE id IN ('.$idPlaceholders.')',
                $bindings,
            );
        }
    }
}
