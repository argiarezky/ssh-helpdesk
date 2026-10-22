<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">Import tiket</h1></x-slot>
    <div class="mx-auto max-w-3xl space-y-4">
        <div class="flex items-center justify-between gap-3"><a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#657585] hover:text-[#25577e]"><span aria-hidden="true">&larr;</span> Kembali ke tiket</a><a href="{{ route('tickets.import.history') }}" class="inline-flex h-8 items-center rounded border border-[#d8e0e7] px-3 text-[10px] font-semibold text-[#536271] hover:bg-[#f8fafc]">Riwayat impor</a></div>
        <section class="overflow-hidden border border-[#dfe5eb] bg-white">
            <header class="border-b border-[#e8edf1] px-4 py-3.5 sm:px-5"><h2 class="text-[13px] font-semibold text-[#2e3d4b]">Impor dari spreadsheet</h2><p class="mt-1 text-[10px] text-[#84909c]">Ekspor tab Google Sheets sebagai CSV, lalu unggah di sini. Maksimal 5.000 baris dan 10 MB.</p></header>
            <div class="space-y-4 p-4 sm:p-5">
                @if ($errors->any())<div class="border-l-2 border-[#b91c1c] bg-[#fef2f2] px-3 py-2.5 text-[11px] text-[#991b1b]">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
                <div class="border border-[#dfe5eb] bg-[#f8fafc] p-3 text-[10px] leading-5 text-[#536271]"><p class="font-semibold text-[#263442]">Kolom yang dikenali</p><p>Ticket ID, Tanggal Masuk, Asal Laporan, Lokasi, Project, Pelapor / Unit, Koordinator, Kategori Masalah, Kategori Gangguan, Device Issue, Deskripsi issue, PIC / Pelapor, Kontak PIC / Vendor, Prioritas, Deliver to Engineer, Teknisi SSH, Tindakan 1, Tindakan 2, SLA, Deadline, Action, Plan tim Engineer, Status, Tanggal Selesai, Durasi, dan Resume Kegiatan.</p><p class="mt-1">Nomor tiket web dibuat otomatis. Ticket ID lama disimpan sebagai referensi. Baris tanpa lokasi/deskripsi dan tiket aktif duplikat akan dilewati. Durasi dari spreadsheet dipertahankan; bila kosong, durasi dihitung dari tanggal masuk dan tanggal selesai.</p></div>
                <form method="POST" action="{{ route('tickets.import.store') }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <label for="spreadsheet" class="block text-[11px] font-medium text-[#536271]">File CSV spreadsheet</label>
                    <input id="spreadsheet" name="spreadsheet" type="file" accept=".csv,text/csv" required class="block w-full rounded border border-[#d8e0e7] px-3 py-2 text-[10px] text-[#536271] file:mr-3 file:rounded file:border-0 file:bg-[#e8edf1] file:px-2.5 file:py-1 file:text-[10px] file:font-semibold file:text-[#263442]">
                    <div class="flex justify-end gap-2 border-t border-[#e8edf1] pt-3"><a href="{{ route('tickets.index') }}" class="inline-flex h-8 items-center rounded border border-[#d8e0e7] px-4 text-[11px] font-semibold text-[#536271]">Batal</a><button class="inline-flex h-8 items-center rounded bg-[#25577e] px-4 text-[11px] font-semibold text-white hover:bg-[#1e496b]" onclick="return confirm('Impor data dari CSV ini sekarang? Baris tiket baru akan ditambahkan ke aplikasi.')">Impor tiket</button></div>
                </form>
            </div>
        </section>
    </div>
</x-app-layout>
