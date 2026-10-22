<x-app-layout>
    <x-slot name="header"><h1 class="text-[14px] font-semibold text-[#263442]">Template Respons</h1></x-slot>
    <div class="mx-auto max-w-5xl space-y-4">
        <a href="{{ route('tickets.index') }}" class="text-[11px] font-medium text-[#657585] hover:text-[#25577e]">&larr; Kembali ke tiket</a>
        @if ($errors->any())<div class="border-l-2 border-[#ba6962] bg-[#fbefed] px-3 py-2.5 text-[11px] text-[#985953]">@foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
        <section class="border border-[#dfe5eb] bg-white p-4 sm:p-5">
            <h2 class="text-[13px] font-semibold text-[#2e3d4b]">Tambah template</h2>
            <form method="POST" action="{{ route('response-templates.store') }}" class="mt-3 grid gap-3 sm:grid-cols-2">@csrf
                <label class="text-[10px] font-medium text-[#536271]">Nama template<input name="title" required maxlength="120" value="{{ old('title') }}" class="mt-1 w-full rounded border-[#d8e0e7] text-[11px]"></label>
                <label class="text-[10px] font-medium text-[#536271]">Isi respons<textarea name="content" required rows="3" class="mt-1 w-full rounded border-[#d8e0e7] text-[11px]">{{ old('content') }}</textarea></label>
                <button class="h-8 justify-self-start rounded bg-[#25577e] px-3 text-[10px] font-semibold text-white">Simpan template</button>
            </form>
        </section>
        <section class="space-y-3">
            @forelse ($templates as $template)
                <div class="border border-[#dfe5eb] bg-white p-4">
                    <form method="POST" action="{{ route('response-templates.update', $template) }}" class="grid gap-3 sm:grid-cols-[1fr_2fr_auto]">@csrf @method('PUT')
                        <label class="text-[10px] font-medium text-[#536271]">Nama<input name="title" required maxlength="120" value="{{ $template->title }}" class="mt-1 w-full rounded border-[#d8e0e7] text-[11px]"></label>
                        <label class="text-[10px] font-medium text-[#536271]">Isi respons<textarea name="content" required rows="3" class="mt-1 w-full rounded border-[#d8e0e7] text-[11px]">{{ $template->content }}</textarea></label>
                        <button class="h-8 self-end rounded border border-[#d8e0e7] px-3 text-[10px] font-semibold text-[#536271]">Simpan</button>
                    </form>
                    <form method="POST" action="{{ route('response-templates.destroy', $template) }}" onsubmit="return confirm('Hapus template ini?')" class="mt-2">@csrf @method('DELETE')<button class="h-8 rounded border border-[#e3c9c7] px-3 text-[10px] font-semibold text-[#a44343]">Hapus</button></form>
                </div>
            @empty
                <div class="border border-dashed border-[#cfd8e0] p-6 text-center text-[11px] text-[#84909c]">Belum ada template respons.</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
