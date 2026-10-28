<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">Tiket HelpDesk</h1></x-slot>

    <div class="space-y-4">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div><h2 class="text-[17px] font-semibold tracking-tight text-[#263442]">Daftar tiket</h2><p class="mt-1 text-[11px] text-[#778594]">Cari, tinjau, dan kelola laporan dukungan teknis.</p></div>
            <div class="flex flex-wrap gap-2"><a href="{{ route('response-templates.index') }}" class="inline-flex h-8 items-center rounded border border-[#d8e0e7] px-3 text-[10px] font-semibold text-[#536271]">Template Respons</a></div>
        </div>



        <form method="GET" action="{{ route('tickets.index') }}" class="grid gap-2 border border-[#dfe5eb] bg-white p-3 sm:grid-cols-3 xl:grid-cols-5">
            <label class="sm:col-span-2 xl:col-span-2"><span class="sr-only">Cari tiket</span><input name="search" value="{{ $filters['search'] }}" placeholder="Cari nomor, pelapor, lokasi, masalah..." class="w-full rounded border-[#d8e0e7] py-2 text-[11px] placeholder:text-[#98a3ad]"></label>
            <select name="status" class="rounded border-[#d8e0e7] py-2 text-[11px]"><option value="">Semua status</option>@foreach (['open', 'pending', 'in_progress', 'resolved', 'closed'] as $option)<option value="{{ $option }}" @selected($filters['status'] === $option)>{{ ['in_progress' => 'In Progress'][$option] ?? ucfirst($option) }}</option>@endforeach</select>
            <select name="priority" class="rounded border-[#d8e0e7] py-2 text-[11px]"><option value="">Semua prioritas</option>@foreach (['low', 'normal', 'high', 'urgent'] as $option)<option value="{{ $option }}" @selected($filters['priority'] === $option)>{{ ['normal' => 'Medium', 'urgent' => 'Critical'][$option] ?? ucfirst($option) }}</option>@endforeach</select>
            <select name="responsible_engineer" class="rounded border-[#d8e0e7] py-2 text-[11px]"><option value="">Semua engineer</option>@foreach (\App\Models\Ticket::RESPONSIBLE_ENGINEERS as $responsibleEngineer)<option value="{{ $responsibleEngineer }}" @selected($filters['responsible_engineer'] === $responsibleEngineer)>{{ $responsibleEngineer }}</option>@endforeach</select>
            <label class="text-[9px] text-[#718091]">Dari tanggal<input name="from" type="date" value="{{ $filters['from'] }}" class="mt-1 w-full rounded border-[#d8e0e7] py-1.5 text-[10px]"></label>
            <label class="text-[9px] text-[#718091]">Sampai tanggal<input name="to" type="date" value="{{ $filters['to'] }}" class="mt-1 w-full rounded border-[#d8e0e7] py-1.5 text-[10px]"></label>
            <select name="sort" class="self-end rounded border-[#d8e0e7] py-2 text-[11px]"><option value="created_at" @selected($filters['sort'] === 'created_at')>Urut: tanggal masuk</option><option value="updated_at" @selected($filters['sort'] === 'updated_at')>Urut: pembaruan</option><option value="ticket_number" @selected($filters['sort'] === 'ticket_number')>Urut: nomor tiket</option><option value="priority" @selected($filters['sort'] === 'priority')>Urut: prioritas</option><option value="status" @selected($filters['sort'] === 'status')>Urut: status</option><option value="reporter_name" @selected($filters['sort'] === 'reporter_name')>Urut: pelapor</option><option value="engineer" @selected($filters['sort'] === 'engineer')>Urut: engineer</option></select>
            <select name="direction" class="self-end rounded border-[#d8e0e7] py-2 text-[11px]"><option value="desc" @selected($filters['direction'] === 'desc')>Menurun</option><option value="asc" @selected($filters['direction'] === 'asc')>Menaik</option></select>
            <label class="flex min-h-8 items-center justify-center gap-2 pr-8 text-[10px] text-[#536271]"><input type="checkbox" name="archived" value="1" @checked($filters['archived'] === '1')> Tampilkan arsip</label>
            <div class="grid w-full grid-cols-1 items-center gap-2 sm:grid-cols-[minmax(0,1fr)_auto] sm:col-span-3 xl:col-span-5">
                <div class="flex items-center gap-2">
                    <a href="{{ route('tickets.export.excel', array_filter($filters, fn ($value) => $value !== '')) }}" title="Unduh laporan Excel dengan format dan warna" class="inline-flex h-8 items-center gap-1.5 rounded bg-[#18794e] px-3 text-[10px] font-semibold text-white hover:bg-[#12633f]"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16v16H4zM8 8h3v3H8zM13 8h3M13 11h3M8 14h3v3H8zM13 14h3M13 17h3"/></svg>Excel berformat</a>
                    <a href="{{ route('tickets.export', array_filter($filters, fn ($value) => $value !== '')) }}" title="CSV berisi data tanpa format sel" class="inline-flex h-8 items-center rounded border border-[#d8e0e7] px-3 text-[10px] font-semibold text-[#536271]">.CSV</a>
                </div>
                <div class="flex items-center justify-end gap-2">
                    <button type="submit" class="h-8 rounded bg-[#25577e] px-4 text-[10px] font-semibold text-white">Terapkan</button>
                    <a href="{{ route('tickets.index', ['reset' => 1]) }}" class="px-2 py-2 text-[10px] text-[#6e7b89]">Reset</a>
                </div>
            </div>
        </form>

        <section x-data="{ selectionMode: false, selectedIds: [], excludedIds: [], selectAllMatching: false, visibleTicketIds: @js($tickets->getCollection()->modelKeys()), total: {{ $tickets->total() }}, get selectedCount() { return this.selectAllMatching ? this.total - this.excludedIds.length : this.selectedIds.length; }, toggleTicket(id, checked) { id = String(id); if (this.selectAllMatching) { this.excludedIds = checked ? this.excludedIds.filter(item => item !== id) : [...new Set([...this.excludedIds, id])]; } else { this.selectedIds = checked ? [...new Set([...this.selectedIds, id])] : this.selectedIds.filter(item => item !== id); } } }" class="overflow-hidden border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
            <header class="flex flex-wrap items-center justify-between gap-2 border-b border-[#e8edf1] px-3.5 py-3">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($tickets->isNotEmpty())<button type="button" @click="selectionMode = ! selectionMode; if (! selectionMode) { selectedIds = []; excludedIds = []; selectAllMatching = false; }" class="inline-flex h-7 w-7 items-center justify-center rounded border border-[#dfe5eb] text-[#718091] hover:bg-[#f3f6f8]" aria-label="Pilih tiket" title="Pilih tiket"><svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 5h16l-6.2 7.1v5.2l-3.6 1.8v-5.2z"/></svg></button>@endif
                    <h3 class="text-[12px] font-semibold text-[#2e3d4b]">{{ $filters['archived'] === '1' ? 'Arsip tiket' : 'Semua tiket' }}</h3>
                    <label x-cloak x-show="selectionMode" class="inline-flex cursor-pointer items-center gap-1.5 text-[10px] font-medium text-[#657585]"><input type="checkbox" :checked="selectAllMatching" @change="selectAllMatching = $event.target.checked; selectedIds = []; excludedIds = []" class="rounded border-[#cbd5de] text-[#39739d]"><span>Pilih semua {{ number_format($tickets->total()) }} hasil</span></label>
                    <p class="text-[10px] text-[#84909c]">{{ number_format($tickets->total()) }} laporan ditemukan</p>
                </div>
            </header>

            @if ($tickets->isEmpty())
                <div class="px-5 py-12 text-center"><h2 class="text-[12px] font-semibold text-[#3b4a59]">Tidak ada tiket ditemukan</h2><p class="mt-1 text-[10px] text-[#84909c]">Ubah pencarian atau filter.</p></div>
            @else
                <form method="POST" action="{{ route('tickets.bulk-action') }}" @submit="if ($event.submitter?.value === 'delete' && ! confirm('Yakin ingin menghapus ' + selectedCount + ' tiket yang dipilih?')) { $event.preventDefault(); return; } if ($event.submitter?.value === 'update') { const label = $event.target.querySelector('[name=status]').selectedOptions[0].text; if (! confirm('Ubah status ' + selectedCount + ' tiket menjadi ' + label + '?')) $event.preventDefault(); } if (['archive','restore'].includes($event.submitter?.value) && ! confirm(($event.submitter.value === 'archive' ? 'Arsipkan ' : 'Pulihkan ') + selectedCount + ' tiket?')) $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="select_all_matching" :value="selectAllMatching ? 1 : 0">
                    <template x-for="excludedId in excludedIds" :key="excludedId"><input type="hidden" name="excluded_ticket_ids[]" :value="excludedId"></template>
                    @foreach ($filters as $key => $value)<input type="hidden" name="filters[{{ $key }}]" value="{{ $value }}">@endforeach
                    <div x-cloak x-show="selectionMode && selectedCount > 0" class="flex flex-col gap-2 border-b border-[#e8edf1] bg-[#f8fafc] px-3.5 py-2.5 sm:flex-row sm:items-center">
                        <span class="text-[10px] font-medium text-[#657585]" x-text="selectedCount + ' tiket dipilih'"></span>
                        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
                            <select name="status" required class="h-8 rounded border-[#d8e0e7] py-1 text-[10px] text-[#526170]"><option value="">Pilih status baru</option>@foreach (['open', 'pending', 'in_progress', 'resolved', 'closed'] as $option)<option value="{{ $option }}">{{ ['in_progress' => 'In Progress'][$option] ?? ucfirst($option) }}</option>@endforeach</select>
                            <button type="submit" name="action" value="update" class="h-8 rounded bg-[#25577e] px-3 text-[10px] font-semibold text-white">Ubah status</button>
                            @if ($filters['archived'] === '1')<button type="submit" name="action" value="restore" formnovalidate class="h-8 rounded border border-[#d8e0e7] bg-white px-3 text-[10px] font-semibold text-[#536271]">Pulihkan</button>@else<button type="submit" name="action" value="archive" formnovalidate class="h-8 rounded border border-[#d8e0e7] bg-white px-3 text-[10px] font-semibold text-[#536271]">Arsipkan</button>@endif
                            <button type="submit" name="action" value="delete" formnovalidate class="h-8 rounded border border-[#e3c9c7] bg-white px-3 text-[10px] font-semibold text-[#a44343]">Hapus pilihan</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto"><table class="w-full min-w-[1160px] text-left">
                        <thead class="bg-[#f7f9fb] text-[9px] font-semibold uppercase tracking-[.1em] text-[#81909e]"><tr><th x-cloak x-show="selectionMode" class="w-9 px-3 py-2.5"><span class="sr-only">Pilih</span></th><th class="px-3 py-2.5">Nomor tiket</th><th class="px-3 py-2.5">Pelapor / Lokasi</th><th class="px-3 py-2.5">Masalah</th><th class="px-3 py-2.5">Engineer</th><th class="px-3 py-2.5">Prioritas</th><th class="px-3 py-2.5">Status</th><th class="px-3 py-2.5">Pembaruan</th><th class="px-3 py-2.5">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-[#edf0f3]">
                            @foreach ($tickets as $ticket)
                                <tr class="transition hover:bg-[#f8fafc]">
                                    <td x-cloak x-show="selectionMode" class="px-3 py-2.5"><input type="checkbox" :name="selectAllMatching ? null : 'ticket_ids[]'" :value="{{ $ticket->id }}" :checked="selectAllMatching ? ! excludedIds.includes('{{ $ticket->id }}') : selectedIds.includes('{{ $ticket->id }}')" @change="toggleTicket('{{ $ticket->id }}', $event.target.checked)" class="rounded border-[#cbd5de] text-[#39739d]" aria-label="Pilih tiket {{ $ticket->ticket_number }}"></td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><span class="font-mono text-[10px] font-semibold text-[#2b6897]">{{ $ticket->ticket_number }}</span>@if ($ticket->legacy_ticket_number)<p class="mt-0.5 text-[9px] text-[#84909c]">Sheet: {{ $ticket->legacy_ticket_number }}</p>@endif</td>
                                    <td class="px-3 py-2.5"><p class="max-w-32 truncate text-[10px] font-medium text-[#354555]">{{ $ticket->pic_name ?: $ticket->reporter_name }}</p><p class="mt-0.5 max-w-32 truncate text-[9px] text-[#84909c]">{{ $ticket->location }}</p>@if ($ticket->project_name)<p class="mt-0.5 max-w-32 truncate text-[9px] text-[#64748b]">{{ $ticket->project_name }}</p>@endif</td>
                                    <td class="max-w-48 truncate px-3 py-2.5 text-[10px] text-[#697887]" title="{{ $ticket->description }}">{{ $ticket->description }}</td>
                                    <td class="max-w-32 truncate px-3 py-2.5 text-[10px] text-[#697887]">{{ $ticket->assigned_engineer_name ?? $ticket->engineer?->name ?? $ticket->technician_name ?? 'Belum ditentukan' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><span @class(['text-[9px] font-semibold', 'text-[#15803d]' => $ticket->priority === 'low', 'text-[#a16207]' => in_array($ticket->priority, ['normal', 'medium'], true), 'text-[#c2410c]' => $ticket->priority === 'high', 'text-[#b91c1c]' => in_array($ticket->priority, ['urgent', 'critical'], true)])>{{ ['urgent' => 'Critical', 'critical' => 'Critical', 'normal' => 'Medium'][$ticket->priority] ?? ucfirst($ticket->priority) }}</span></td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><x-ticket-status :status="$ticket->status" /></td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-[10px] text-[#84909c]">{{ $ticket->updated_at->format('d M Y, H:i') }} @if (in_array($ticket->status, ['open', 'pending', 'in_progress'], true) && $ticket->updated_at->lte(now()->subDays(3)))<span class="ml-1 font-semibold text-[#b45309]" title="Belum diperbarui lebih dari 3 hari">Lama</span>@endif</td>
                                    <td class="whitespace-nowrap px-3 py-2.5"><a href="{{ route('tickets.show', $ticket) }}" class="text-[10px] font-semibold text-[#2b6897] hover:underline">Detail</a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table></div>
                    <div class="border-t border-[#e8edf1] px-3.5 py-2">{{ $tickets->links('vendor.pagination.compact') }}</div>
                </form>
            @endif
        </section>
    </div>
</x-app-layout>