<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Akses engineer · SSH HelpDesk · Network Operations Center</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-[#263442] antialiased">
    <main class="grid min-h-screen lg:grid-cols-[.92fr_1.08fr]">
        <section class="flex min-h-[230px] flex-col justify-between bg-[#17283a] px-6 py-6 text-white sm:px-10 sm:py-8 lg:min-h-screen lg:px-14 lg:py-10 xl:px-20"><a href="{{ route('login') }}" class="flex w-fit items-center gap-2.5"><span class="flex h-8 w-8 items-center justify-center rounded bg-[#dce9f3] text-[11px] font-bold tracking-wide text-[#244766]">SSH</span><span><span class="block text-[13px] font-semibold">SSH HelpDesk</span><span class="block text-[10px] text-[#a8b7c5]">Network Operations Center</span></span></a><div class="max-w-md py-8 lg:py-0"><p class="text-[10px] font-semibold uppercase tracking-[.16em] text-[#9eb7cb]">Engineer workspace</p><h1 class="mt-3 text-[28px] font-semibold leading-tight tracking-tight sm:text-[36px]">Akses layanan untuk tim teknis.</h1><p class="mt-3 max-w-sm text-[12px] leading-6 text-[#bdc9d4]">Akun dikelola secara terpusat untuk melindungi informasi layanan dan pelanggan.</p></div><p class="hidden text-[10px] text-[#859aad] lg:block">SSH Network Solution <span class="px-1">·</span> Internal use</p></section>
        <section class="flex items-center justify-center px-6 py-12 sm:px-10 lg:px-14"><div class="w-full max-w-[340px]"><p class="text-[11px] font-medium text-[#39739d]">Akses workspace</p><h2 class="mt-1.5 text-[22px] font-semibold tracking-tight">Pendaftaran tidak tersedia</h2><p class="mt-3 text-[11px] leading-5 text-[#778594]">Pendaftaran publik dinonaktifkan. Hubungi administrator HelpDesk untuk menyiapkan akun engineer.</p><div class="mt-6 border-y border-[#e5eaee] py-4"><p class="text-[11px] font-medium text-[#536271]">Sudah memiliki akun?</p><p class="mt-1 text-[10px] leading-5 text-[#778594]">Masuk menggunakan email dan password yang diberikan administrator.</p></div><a href="{{ route('login') }}" class="mt-6 inline-flex h-9 w-full items-center justify-center rounded bg-[#25577e] px-4 text-[11px] font-semibold text-white hover:bg-[#1e496b]">Kembali ke login</a></div></section>
    </main>
</body>
</html>
