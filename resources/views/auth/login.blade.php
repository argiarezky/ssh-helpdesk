<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Masuk · SSH HelpDesk · Network Operations Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-[#263442] antialiased">
    <main class="grid min-h-screen lg:grid-cols-[.92fr_1.08fr]">
        <section class="flex min-h-[250px] flex-col justify-between bg-[#17283a] px-6 py-6 text-white sm:px-10 sm:py-8 lg:min-h-screen lg:px-14 lg:py-10 xl:px-20">
            <a href="{{ route('login') }}" class="flex w-fit items-center gap-2.5"><span class="flex h-8 w-8 items-center justify-center rounded bg-[#dce9f3] text-[11px] font-bold tracking-wide text-[#244766]">SSH</span><span><span class="block text-[13px] font-semibold">SSH HelpDesk</span><span class="block text-[10px] text-[#a8b7c5]">Network Operations Center</span></span></a>
            <div class="max-w-lg py-8 lg:py-0"><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9eb7cb]">Engineer workspace</p><h1 class="mt-3 max-w-md text-[28px] font-semibold leading-[1.15] tracking-tight sm:text-[36px]">Semua laporan. Satu alur penanganan.</h1><p class="mt-3 max-w-sm text-[12px] leading-6 text-[#bdc9d4]">Ruang kerja internal untuk memantau tiket, koordinasi teknisi, dan riwayat penyelesaian layanan.</p>
                <div class="mt-8 max-w-sm border-y border-white/15">
                    <div class="flex items-center gap-3 border-b border-white/10 py-3"><span class="font-mono text-[10px] text-[#9eb7cb]">01</span><span class="text-[11px] text-[#e1e8ee]">Terima dan catat laporan</span><span class="ml-auto text-[9px] text-[#859aad]">INTAKE</span></div>
                    <div class="flex items-center gap-3 border-b border-white/10 py-3"><span class="font-mono text-[10px] text-[#9eb7cb]">02</span><span class="text-[11px] text-[#e1e8ee]">Tetapkan engineer dan prioritas</span><span class="ml-auto text-[9px] text-[#859aad]">ASSIGN</span></div>
                    <div class="flex items-center gap-3 py-3"><span class="font-mono text-[10px] text-[#9eb7cb]">03</span><span class="text-[11px] text-[#e1e8ee]">Pantau sampai terselesaikan</span><span class="ml-auto text-[9px] text-[#859aad]">RESOLVE</span></div>
                </div>
            </div>
            <p class="hidden text-[10px] text-[#859aad] lg:block">SSH Network Solution <span class="px-1">·</span> Internal use</p>
        </section>

        <section class="flex items-center justify-center px-6 py-10 sm:px-10 lg:px-14"><div class="w-full max-w-[340px]">
            <div class="mb-7"><p class="text-[11px] font-medium text-[#39739d]">Akses internal</p><h2 class="mt-1.5 text-[22px] font-semibold tracking-tight text-[#263442]">Masuk ke akun</h2><p class="mt-1.5 text-[11px] leading-5 text-[#778594]">Gunakan kredensial akun engineer Anda.</p></div>
            <x-auth-session-status class="mb-4" :status="session('status')" />
            @if ($errors->any())<div class="mb-4 border-l-2 border-[#ba6962] bg-[#fbefed] px-3 py-2.5 text-[11px] text-[#985953]">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login') }}" class="space-y-4">
                @csrf
                <div><label for="email" class="mb-1.5 block text-[11px] font-medium text-[#536271]">Email</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" placeholder="nama@perusahaan.com" class="w-full rounded border-[#d8e0e7] bg-white px-3 py-2.5 text-[11px] placeholder:text-[#9aa5af] focus:border-[#39739d] focus:ring-[#d7e5ef]"></div>
                <div><div class="mb-1.5 flex items-center justify-between"><label for="password" class="text-[11px] font-medium text-[#536271]">Password</label>@if (Route::has('password.request'))<a href="{{ route('password.request') }}" class="text-[10px] font-medium text-[#2b6897] hover:underline">Lupa password?</a>@endif</div><input id="password" name="password" type="password" required autocomplete="current-password" placeholder="Masukkan password" class="w-full rounded border-[#d8e0e7] bg-white px-3 py-2.5 text-[11px] placeholder:text-[#9aa5af] focus:border-[#39739d] focus:ring-[#d7e5ef]"></div>
                <label class="flex items-center gap-2 pt-0.5 text-[10px] text-[#697887]"><input type="checkbox" name="remember" class="rounded border-[#c7d0d8] text-[#25577e] focus:ring-[#d7e5ef]">Ingat saya</label>
                <button class="mt-1 w-full rounded bg-[#25577e] px-4 py-2.5 text-[11px] font-semibold text-white transition hover:bg-[#1e496b] focus:outline-none focus:ring-2 focus:ring-[#b9d0e1] focus:ring-offset-2">Masuk ke dashboard</button>
            </form>
            <div class="mt-6 border-t border-[#e5eaee] pt-4"><p class="text-[10px] leading-5 text-[#778594]">Belum memiliki akses? Hubungi administrator untuk menyiapkan akun engineer.</p></div>
            <p class="mt-8 text-center text-[9px] text-[#9aa5af]">SSH HelpDesk <span class="px-1">·</span> Internal use</p>
        </div></section>
    </main>
</body>
</html>
