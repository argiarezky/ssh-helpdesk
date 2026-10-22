<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">Buat tiket baru</h1></x-slot>
    <div class="mx-auto max-w-5xl space-y-4">
        <a href="{{ route('tickets.index') }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium text-[#657585] hover:text-[#25577e]"><span aria-hidden="true">&larr;</span> Kembali ke tiket</a>
        <section class="overflow-hidden border border-[#dfe5eb] bg-white shadow-[0_1px_2px_rgba(24,39,56,.04)]"><header class="border-b border-[#e8edf1] px-4 py-3.5 sm:px-5"><h2 class="text-[13px] font-semibold text-[#2e3d4b]">Informasi tiket</h2><p class="mt-0.5 text-[10px] text-[#84909c]">Lengkapi detail laporan dukungan teknis.</p></header><form method="POST" enctype="multipart/form-data" action="{{ route('tickets.store') }}" class="p-4 sm:p-5">@csrf @include('tickets._form')</form></section>
    </div>
</x-app-layout>
