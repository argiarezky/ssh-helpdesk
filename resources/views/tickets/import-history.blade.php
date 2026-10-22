<div>
    <!-- Simplicity is the essence of happiness. - Cedric Bledsoe -->
</div>
<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">Riwayat impor</h1></x-slot>

    <div class="space-y-4">
        <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
            <div><h2 class="text-[17px] font-semibold tracking-tight text-[#263442]">Riwayat impor .CSV</h2><p class="mt-1 text-[11px] text-[#778594]">Catatan file yang pernah diimpor, jumlah tiket yang masuk, dan baris yang dilewati.</p></div>
            <a href="{{ route('tickets.import.create') }}" class="inline-flex h-8 items-center justify-center rounded bg-[#25577e] px-3 text-[10px] font-semibold text-white hover:bg-[#1e496b]">Impor .CSV</a>
        </div>

        <section class="overflow-hidden border border-[#dfe5eb] bg-white">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] border-collapse text-left">
                    <thead class="bg-[#f5f7f9] text-[9px] font-semibold uppercase tracking-wide text-[#718091]"><tr><th class="px-3.5 py-3">Tanggal</th><th class="px-3.5 py-3">File</th><th class="px-3.5 py-3">Diimpor oleh</th><th class="px-3.5 py-3 text-right">Baris dibaca</th><th class="px-3.5 py-3 text-right">Ditambahkan</th><th class="px-3.5 py-3 text-right">Dilewati</th></tr></thead>
                    <tbody class="divide-y divide-[#edf0f3]">
                        @forelse ($imports as $import)
                            <tr class="text-[10px] text-[#536271] hover:bg-[#fafbfc]"><td class="whitespace-nowrap px-3.5 py-3">{{ $import->created_at->format('d M Y, H:i') }}</td><td class="max-w-[250px] truncate px-3.5 py-3 font-medium text-[#354555]" title="{{ $import->original_filename }}">{{ $import->original_filename }}</td><td class="px-3.5 py-3">{{ $import->imported_by_name }}</td><td class="px-3.5 py-3 text-right tabular-nums">{{ number_format($import->total_rows) }}</td><td class="px-3.5 py-3 text-right font-semibold text-[#15803d] tabular-nums">{{ number_format($import->created_count) }}</td><td class="px-3.5 py-3 text-right font-semibold text-[#b45309] tabular-nums">{{ number_format($import->skipped_count) }}</td></tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-[11px] text-[#84909c]">Belum ada riwayat impor.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($imports->hasPages())<div class="border-t border-[#e8edf1] px-3.5 py-2">{{ $imports->links('vendor.pagination.compact') }}</div>@endif
        </section>
    </div>
</x-app-layout>
