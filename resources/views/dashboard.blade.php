<x-app-layout>
    <x-slot name="header">
        <h1 class="text-[14px] font-semibold text-[#263442]">Dashboard</h1>
    </x-slot>

    <div class="space-y-4">
        <section class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div><p class="text-[11px] font-medium uppercase tracking-[.12em] text-[#718091]">Service desk overview</p><h2 class="mt-1 text-[18px] font-semibold tracking-tight text-[#263442]">Selamat datang, {{ Auth::user()->name }}</h2><p class="mt-1 text-[11px] text-[#778594]">Pantau antrean dan progres dukungan jaringan hari ini.</p></div>
        </section>

        <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 items-end gap-3 border border-[#dfe5eb] bg-white p-3 sm:grid-cols-[1fr_auto_1fr]">
            <div class="flex flex-col items-start gap-1.5 sm:col-start-1">
                <span class="text-[10px] font-semibold text-[#536271]">Rentang tanggal dibuat</span>
                <div class="flex items-end gap-2">
                    <label class="text-[9px] text-[#718091]">Dari<input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded border-[#d8e0e7] py-1.5 text-[10px]"></label>
                    <label class="text-[9px] text-[#718091]">Sampai<input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded border-[#d8e0e7] py-1.5 text-[10px]"></label>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2 sm:col-start-3">
                <button class="h-8 rounded bg-[#25577e] px-3 text-[10px] font-semibold text-white">Terapkan</button>
                <a href="{{ route('dashboard') }}" class="px-2 py-2 text-[10px] text-[#6e7b89]">Reset</a>
            </div>
        </form>

        @php($kpis = [ ['Total Tiket', $totalTickets, 'text-[#315f81]', 'bg-[#e9f0f5]', '#315f81', 'all'], ['Open', $statusCounts['open'], 'text-[#64748B]', 'bg-[#f1f5f9]', '#64748B', 'open'], ['In Progress', $statusCounts['in_progress'], 'text-[#2563EB]', 'bg-[#eff6ff]', '#2563EB', 'progress'], ['Resolved', $statusCounts['resolved'], 'text-[#16A34A]', 'bg-[#f0fdf4]', '#16A34A', 'resolved'], ['Closed', $statusCounts['closed'], 'text-[#475569]', 'bg-[#f1f5f9]', '#475569', 'closed'], ['Pending', $statusCounts['pending'], 'text-[#D97706]', 'bg-[#fff7ed]', '#D97706', 'pending'] ])
        <section aria-label="Key performance indicators" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 xl:grid-cols-6">
            @foreach ($kpis as [$label, $value, $color, $iconBg, $accent, $icon])
                <article class="relative min-w-0 overflow-hidden border border-[#e0e6ec] bg-white px-3.5 py-3 shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                    <span class="absolute inset-y-0 left-0 w-[3px]" style="background-color: {{ $accent }}"></span>
                    <div class="flex items-start justify-between gap-2"><div><p class="text-[10px] font-medium text-[#788695]">{{ $label }}</p><p class="mt-1 text-[22px] font-semibold leading-none tracking-tight tabular-nums text-[#263442]">{{ number_format($value) }}</p></div><span class="flex h-7 w-7 shrink-0 items-center justify-center rounded {{ $iconBg }} {{ $color }}">
                        <svg class="h-[15px] w-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
                            @if ($icon === 'all')<path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5"/>
                            @elseif ($icon === 'open')<circle cx="12" cy="12" r="8.5"/><path d="M12 7v5l3 2"/>
                            @elseif ($icon === 'progress')<path d="M4 7h16M4 12h10M4 17h6"/><circle cx="18" cy="16" r="3"/>
                            @elseif ($icon === 'resolved')<path d="m5 12 4 4L19 6"/>
                            @elseif ($icon === 'closed')<rect x="4" y="4" width="16" height="16" rx="2"/><path d="m8 12 2.5 2.5L16 9"/>
                            @else<circle cx="12" cy="12" r="8.5"/><path d="M12 8v4M12 16h.01"/>@endif
                        </svg>
                    </span></div>
                </article>
            @endforeach
        </section>

        <section aria-label="Ticket analytics" class="grid items-stretch gap-3 xl:grid-cols-3">
            <article class="flex min-w-0 flex-col border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                <header class="border-b border-[#e8edf1] px-3.5 py-3"><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Status Breakdown</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Proporsi tiket menurut status</p></header>
                <div class="flex flex-1 flex-col items-center px-3.5 py-2">
                    @if (count($statusChart) > 0)
                        <svg viewBox="0 0 240 205" class="h-[176px] w-full max-w-[260px]" role="img" aria-label="3D pie chart distribusi tiket berdasarkan status">
                            @foreach ($statusChart as $segment)
                                @for ($depth = 12; $depth >= 2; $depth -= 2)<path d="{{ $segment['path'] }}" transform="translate(0, {{ $depth }})" fill="{{ $segment['sideColor'] }}" />@endfor
                            @endforeach
                            @foreach ($statusChart as $segment)
                                <path d="{{ $segment['path'] }}" data-label="{{ $segment['label'] }}" data-count="{{ $segment['count'] }}" fill="{{ $segment['color'] }}" stroke="white" stroke-width="1.5" />
                                @if ($segment['showLabel'])<text x="{{ $segment['labelX'] }}" y="{{ $segment['labelY'] }}" text-anchor="middle" dominant-baseline="middle" fill="white" stroke="{{ $segment['sideColor'] }}" stroke-width="2.5" paint-order="stroke" font-size="10" font-weight="700">{{ $segment['count'] }}</text>@endif
                            @endforeach
                        </svg>
                    @else
                        <div class="flex h-[176px] items-center justify-center"><div class="flex h-28 w-28 items-center justify-center rounded-full border-[14px] border-[#edf1f4] text-center"><span><span class="block text-[20px] font-semibold text-[#465666]">0</span><span class="block text-[9px] text-[#8895a1]">tiket</span></span></div></div>
                    @endif
                    <div class="grid w-full grid-cols-2 gap-x-3 gap-y-1.5 border-t border-[#edf0f3] pt-2.5 text-[10px]">
                        @foreach ([['Open', 'open', '#64748B'], ['In Progress', 'in_progress', '#2563EB'], ['Resolved', 'resolved', '#16A34A'], ['Closed', 'closed', '#475569'], ['Pending', 'pending', '#D97706']] as [$label, $status, $color])
                            <div class="flex min-w-0 items-center gap-1.5"><i class="h-2 w-2 shrink-0 rounded-sm" style="background-color: {{ $color }}"></i><span class="min-w-0 flex-1 truncate text-[#697887]">{{ $label }}</span><span class="tabular-nums text-[#354555]">{{ $statusCounts[$status] }}</span></div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="flex min-w-0 flex-col border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                <header class="border-b border-[#e8edf1] px-3.5 py-3"><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Koordinator</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Distribusi tiket berdasarkan koordinator</p></header>
                <div class="flex flex-1 flex-col items-center px-3.5 py-2">
                    @if (count($coordinatorChart) > 0)
                        <svg viewBox="0 0 240 205" class="h-[176px] w-full max-w-[260px]" role="img" aria-label="3D doughnut chart distribusi tiket berdasarkan koordinator">
                            @foreach ($coordinatorChart as $segment)
                                @for ($depth = 12; $depth >= 2; $depth -= 2)<path d="{{ $segment['path'] }}" transform="translate(0, {{ $depth }})" fill="{{ $segment['sideColor'] }}" fill-rule="evenodd" />@endfor
                            @endforeach
                            @foreach ($coordinatorChart as $segment)<path d="{{ $segment['path'] }}" data-label="{{ $segment['label'] }}" data-count="{{ $segment['count'] }}" fill="{{ $segment['color'] }}" fill-rule="evenodd" stroke="white" stroke-width="1.5" />@endforeach
                            <text x="120" y="72" text-anchor="middle" dominant-baseline="middle" fill="#364757" font-size="21" font-weight="700">{{ $totalTickets }}</text>
                            <text x="120" y="88" text-anchor="middle" dominant-baseline="middle" fill="#84909c" font-size="9" font-weight="500">TOTAL TIKET</text>
                        </svg>
                    @else
                        <div class="flex h-[176px] items-center justify-center"><div class="flex h-28 w-28 items-center justify-center rounded-full border-[14px] border-[#edf1f4] text-center"><span><span class="block text-[20px] font-semibold text-[#465666]">0</span><span class="block text-[9px] text-[#8895a1]">tiket</span></span></div></div>
                    @endif
                    <div class="max-h-[92px] w-full space-y-1 overflow-y-auto border-t border-[#edf0f3] pt-2.5 text-[10px]">
                        @forelse ($coordinatorChart as $segment)
                            <div class="flex items-center gap-1.5"><i class="h-2 w-2 shrink-0 rounded-sm" style="background-color: {{ $segment['color'] }}"></i><span class="min-w-0 flex-1 truncate text-[#697887]">{{ $segment['label'] }}</span><span class="tabular-nums text-[#354555]">{{ $segment['count'] }}</span></div>
                        @empty
                            <p class="text-center text-[#8995a1]">Belum ada data koordinator.</p>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="flex min-w-0 flex-col border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                <header class="border-b border-[#e8edf1] px-3.5 py-3"><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Priority Breakdown</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Urgent ditampilkan sebagai Critical</p></header>
                <div class="flex flex-1 flex-col px-3 py-2">
                    <svg viewBox="0 0 400 190" class="h-[176px] w-full" role="img" aria-label="Grafik batang vertikal jumlah tiket berdasarkan prioritas">
                        @foreach ($priorityAxis as $line)
                            <line x1="38" y1="{{ $line['y'] }}" x2="390" y2="{{ $line['y'] }}" stroke="#e9edf1" stroke-width="1" />
                            <text x="30" y="{{ $line['y'] + 3 }}" text-anchor="end" fill="#84909c" font-size="9">{{ $line['label'] }}</text>
                        @endforeach
                        @foreach ($priorityBars as $bar)
                            <rect x="{{ $bar['x'] }}" y="{{ 160 - $bar['height'] }}" width="42" height="{{ max($bar['height'], 0) }}" rx="2" data-label="{{ $bar['label'] }}" data-count="{{ $bar['count'] }}" fill="{{ $bar['color'] }}" />
                            <text x="{{ $bar['x'] + 21 }}" y="{{ max(151 - $bar['height'], 13) }}" text-anchor="middle" fill="#495766" font-size="10" font-weight="600">{{ $bar['count'] }}</text>
                            <text x="{{ $bar['x'] + 21 }}" y="178" text-anchor="middle" fill="#697887" font-size="10">{{ $bar['label'] }}</text>
                        @endforeach
                    </svg>
                    <p class="mt-auto border-t border-[#edf0f3] pt-2.5 text-[10px] text-[#84909c]">Prioritas <span class="font-medium text-[#16a34a]">Low</span> <span>·</span> <span class="font-medium text-[#eab308]">Medium</span> <span>·</span> <span class="font-medium text-[#ea580c]">High</span> <span>·</span> <span class="font-medium text-[#dc2626]">Critical</span></p>
                </div>
            </article>
        </section>

        <section class="grid items-start gap-3 xl:grid-cols-[minmax(0,1.8fr)_minmax(250px,.8fr)]">
            <article class="min-w-0 overflow-hidden border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                <header class="flex items-center justify-between gap-3 border-b border-[#e8edf1] px-3.5 py-3">
                    <div><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Tiket Terbaru</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Delapan tiket yang terakhir dibuat</p></div>
                    <a href="{{ route('tickets.index') }}" class="shrink-0 text-[10px] font-semibold text-[#2b6897] hover:underline">Lihat semua tiket <span aria-hidden="true">-&gt;</span></a>
                </header>
                @if ($recentTickets->isEmpty())
                    <div class="px-5 py-10 text-center"><p class="text-[12px] font-semibold text-[#3b4a59]">Belum ada tiket</p><p class="mt-1 text-[10px] text-[#84909c]">Tiket yang dibuat akan muncul di sini.</p></div>
                @else
                    <div class="overflow-x-auto"><table class="w-full min-w-[980px] text-left">
                        <thead class="bg-[#f7f9fb] text-[9px] font-semibold uppercase tracking-[.1em] text-[#81909e]"><tr><th class="px-3 py-2.5">Nomor tiket</th><th class="px-3 py-2.5">Pelapor</th><th class="px-3 py-2.5">Masalah</th><th class="px-3 py-2.5">Koordinator</th><th class="px-3 py-2.5">Engineer</th><th class="px-3 py-2.5">Prioritas</th><th class="px-3 py-2.5">Status</th><th class="px-3 py-2.5">Dibuat</th><th class="px-3 py-2.5">Aksi</th></tr></thead>
                        <tbody class="divide-y divide-[#edf0f3]">@foreach ($recentTickets as $ticket)
                            <tr class="transition hover:bg-[#f8fafc]"><td class="whitespace-nowrap px-3 py-2.5"><a href="{{ route('tickets.show', $ticket) }}" class="font-mono text-[10px] font-semibold text-[#2b6897] hover:underline">{{ $ticket->ticket_number }}</a></td><td class="max-w-32 truncate px-3 py-2.5 text-[10px] text-[#354555]">{{ $ticket->reporter_name }}</td><td class="max-w-48 truncate px-3 py-2.5 text-[10px] text-[#697887]" title="{{ $ticket->description }}">{{ $ticket->description }}</td><td class="max-w-32 truncate px-3 py-2.5 text-[10px] text-[#697887]">{{ $ticket->coordinator_name }}</td><td class="max-w-32 truncate px-3 py-2.5 text-[10px] text-[#697887]">{{ $ticket->assigned_engineer_name ?? $ticket->engineer?->name ?? $ticket->technician_name ?? 'Belum ditentukan' }}</td><td class="whitespace-nowrap px-3 py-2.5"><span @class(['text-[9px] font-semibold', 'text-[#15803d]' => $ticket->priority === 'low', 'text-[#a16207]' => in_array($ticket->priority, ['normal', 'medium'], true), 'text-[#c2410c]' => $ticket->priority === 'high', 'text-[#b91c1c]' => in_array($ticket->priority, ['urgent', 'critical'], true)])>{{ ['urgent' => 'Critical', 'critical' => 'Critical', 'normal' => 'Medium'][$ticket->priority] ?? ucfirst($ticket->priority) }}</span></td><td class="whitespace-nowrap px-3 py-2.5"><x-ticket-status :status="$ticket->status" /></td><td class="whitespace-nowrap px-3 py-2.5 text-[10px] text-[#84909c]">{{ $ticket->created_at->format('d M Y') }}</td><td class="whitespace-nowrap px-3 py-2.5"><a href="{{ route('tickets.show', $ticket) }}" class="text-[10px] font-semibold text-[#2b6897] hover:underline">Detail</a></td></tr>
                        @endforeach</tbody>
                    </table></div>
                @endif
            </article>

            <article class="overflow-hidden border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
                <header class="flex items-center justify-between gap-3 border-b border-[#e8edf1] px-3.5 py-3"><div><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Tiket Aktif</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Perlu tindak lanjut</p></div><span class="text-[10px] tabular-nums text-[#718091]">{{ $followUpTickets->count() }} tiket</span></header>
                @forelse ($followUpTickets as $ticket)
                    <a href="{{ route('tickets.show', $ticket) }}" class="block border-b border-[#edf0f3] px-3.5 py-3 last:border-b-0 hover:bg-[#f8fafc]"><div class="flex items-center justify-between gap-2"><span class="font-mono text-[10px] font-semibold text-[#2b6897]">{{ $ticket->ticket_number }}</span><x-ticket-status :status="$ticket->status" /></div><p class="mt-1.5 line-clamp-2 text-[11px] font-medium leading-4 text-[#354555]">{{ $ticket->description }}</p><div class="mt-2 flex items-center justify-between gap-2"><span @class(['text-[9px] font-semibold uppercase tracking-wide', 'text-[#15803d]' => $ticket->priority === 'low', 'text-[#a16207]' => in_array($ticket->priority, ['normal', 'medium'], true), 'text-[#c2410c]' => $ticket->priority === 'high', 'text-[#b91c1c]' => in_array($ticket->priority, ['urgent', 'critical'], true)])>Prioritas {{ ['urgent' => 'Critical', 'critical' => 'Critical', 'normal' => 'Medium'][$ticket->priority] ?? ucfirst($ticket->priority) }}</span><time class="text-[9px] text-[#8995a1]">{{ $ticket->updated_at->diffForHumans() }}</time></div></a>
                @empty
                    <div class="px-4 py-8 text-center"><p class="text-[11px] font-medium text-[#465666]">Tidak ada tiket aktif</p><p class="mt-1 text-[10px] text-[#8995a1]">Open, pending, dan in progress tampil di sini.</p></div>
                @endforelse
            </article>
        </section>

        <section class="overflow-hidden border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]">
            <header class="border-b border-[#e8edf1] px-3.5 py-3"><h3 class="text-[12px] font-semibold text-[#2e3d4b]">Aktivitas Terbaru</h3><p class="mt-0.5 text-[10px] text-[#84909c]">Aktivitas tiket terbaru di workspace</p></header>
            <ol class="divide-y divide-[#edf0f3]">
                @forelse ($recentActivities as $activity)
                    <li class="flex flex-col gap-1 px-3.5 py-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="text-[10px] font-medium text-[#465666]">{{ $activity->message }}</p><span class="text-[9px] text-[#84909c]">{{ $activity->user?->name ?? 'Sistem' }} · {{ $activity->created_at->diffForHumans() }}</span></div>@if ($activity->ticket)<a href="{{ route('tickets.show', $activity->ticket) }}" class="font-mono text-[9px] font-semibold text-[#2b6897]">{{ $activity->ticket->ticket_number }}</a>@endif</li>
                @empty
                    <li class="px-4 py-5 text-center text-[10px] text-[#84909c]">Belum ada aktivitas tiket.</li>
                @endforelse
            </ol>
        </section>
    </div>
</x-app-layout>
