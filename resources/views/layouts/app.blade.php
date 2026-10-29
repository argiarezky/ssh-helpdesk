<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SSH HelpDesk · Network Operations Center</title>
    <style>[x-cloak] { display: none !important; }</style>
    <script>
        try {
            if (localStorage.getItem('ssh-helpdesk-sidebar-collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed');
            }
        } catch {}
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="helpdesk-shell bg-white font-sans text-[#292f2b] antialiased" x-data="{ sidebarOpen: false, sidebarCollapsed: localStorage.getItem('ssh-helpdesk-sidebar-collapsed') === 'true' }" x-init="$watch('sidebarCollapsed', value => localStorage.setItem('ssh-helpdesk-sidebar-collapsed', value))">
    <div class="min-h-screen">
        <aside class="helpdesk-sidebar fixed inset-y-0 left-0 z-40 flex w-[248px] -translate-x-full flex-col border-r border-[#202923] bg-[#202923] text-white lg:translate-x-0" :class="[sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-[248px]']">
            <div class="flex h-[62px] items-center border-b border-white/10 px-4" :class="sidebarCollapsed ? 'lg:justify-center lg:px-2' : 'justify-between'">
                <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded bg-[#276749] text-[11px] font-bold tracking-wide text-white">SSH</span>
                    <span x-show="!sidebarCollapsed" x-cloak class="min-w-0"><span class="block truncate text-[13px] font-semibold tracking-tight">SSH HelpDesk</span><span class="mt-0.5 block text-[10px] text-[#a8b7c5]">Network Operations Center</span></span>
                </a>
                <button @click="sidebarCollapsed = !sidebarCollapsed" class="hidden rounded p-1.5 text-[#9aabba] hover:bg-white/10 hover:text-white lg:block" :class="sidebarCollapsed ? 'lg:hidden' : ''" aria-label="Ciutkan sidebar" title="Ciutkan sidebar"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m14 6-6 6 6 6"/></svg></button>
                <button x-show="sidebarCollapsed" x-cloak @click="sidebarCollapsed = false" class="hidden rounded p-1.5 text-[#9aabba] hover:bg-white/10 hover:text-white lg:block" aria-label="Lebarkan sidebar" title="Lebarkan sidebar"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m10 6 6 6-6 6"/></svg></button>
                <button @click="sidebarOpen = false" class="rounded p-1.5 text-[#9aabba] hover:bg-white/10 lg:hidden" aria-label="Tutup navigasi"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m6 6 12 12M18 6 6 18"/></svg></button>
            </div>

            <nav class="flex-1 overflow-y-auto px-3 py-5" :class="sidebarCollapsed ? 'lg:px-2' : ''">
                <p x-show="!sidebarCollapsed" x-cloak class="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[.14em] text-[#899aab]">Workspace</p>
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" title="Dashboard" @class(['group flex items-center gap-3 rounded-md px-3 py-2.5 text-[12px] font-medium transition', 'bg-[#276749] text-white' => request()->routeIs('dashboard'), 'text-[#c2ced8] hover:bg-white/8 hover:text-white' => ! request()->routeIs('dashboard')]) :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3.5" y="3.5" width="7" height="7" rx="1"/><rect x="13.5" y="3.5" width="7" height="7" rx="1"/><rect x="3.5" y="13.5" width="7" height="7" rx="1"/><rect x="13.5" y="13.5" width="7" height="7" rx="1"/></svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
                    </a>
                    <a href="{{ route('tickets.index') }}" title="Tiket" @class(['group flex items-center gap-3 rounded-md px-3 py-2.5 text-[12px] font-medium transition', 'bg-[#276749] text-white' => request()->routeIs('tickets.index', 'tickets.show', 'tickets.edit'), 'text-[#c2ced8] hover:bg-white/8 hover:text-white' => ! request()->routeIs('tickets.index', 'tickets.show', 'tickets.edit')]) :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4.5h14v15H5zM8 8h8M8 12h8M8 16h5"/></svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Tiket</span>
                        @if (isset($statusCounts) && $statusCounts['open'] + $statusCounts['pending'] + $statusCounts['in_progress'] > 0)
                            <span x-show="!sidebarCollapsed" x-cloak class="ml-auto rounded bg-[#1f5139] px-1.5 py-0.5 text-[10px] text-white">{{ $statusCounts['open'] + $statusCounts['pending'] + $statusCounts['in_progress'] }}</span>
                        @endif
                    </a>
                    <a href="{{ route('tickets.create') }}" title="Buat Tiket" @class(['group flex items-center gap-3 rounded-md px-3 py-2.5 text-[12px] font-semibold transition', 'bg-[#276749] text-white' => request()->routeIs('tickets.create'), 'text-[#c2ced8] hover:bg-white/8 hover:text-white' => ! request()->routeIs('tickets.create')]) :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Buat Tiket</span>
                    </a>
                    <a href="{{ route('response-templates.index') }}" title="Template Respons" @class(['group flex items-center gap-3 rounded-md px-3 py-2.5 text-[12px] font-medium transition', 'bg-[#276749] text-white' => request()->routeIs('response-templates.*'), 'text-[#c2ced8] hover:bg-white/8 hover:text-white' => ! request()->routeIs('response-templates.*')]) :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                        <svg class="h-[17px] w-[17px] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M5 4h14v16H5zM8 8h8M8 12h8M8 16h5"/></svg>
                        <span x-show="!sidebarCollapsed" x-cloak>Template Respons</span>
                    </a>
                </div>
                <p x-show="!sidebarCollapsed" x-cloak class="mb-2 mt-7 px-3 text-[10px] font-semibold uppercase tracking-[.14em] text-[#899aab]">Account</p>
                <a href="{{ route('profile.edit') }}" title="Settings" @class(['group flex items-center gap-3 rounded-md px-3 py-2.5 text-[12px] font-medium transition', 'bg-[#276749] text-white' => request()->routeIs('profile.*'), 'text-[#c2ced8] hover:bg-white/8 hover:text-white' => ! request()->routeIs('profile.*')]) :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md text-[#c2ced8]">
                        <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 7h9m4 0h3M4 17h3m4 0h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></svg>
                    </span>
                    <span x-show="!sidebarCollapsed" x-cloak>Settings</span>
                </a>
            </nav>

            <div class="border-t border-white/10 p-3" :class="sidebarCollapsed ? 'lg:px-2' : ''">
                <div class="flex items-center gap-2.5 rounded-md bg-white/5 px-2 py-2" :class="sidebarCollapsed ? 'lg:justify-center lg:px-0' : ''">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#276749] text-[12px] font-semibold text-white">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
                    <span x-show="!sidebarCollapsed" x-cloak class="min-w-0 flex-1"><span class="block truncate text-[11px] font-semibold">{{ Auth::user()->name }}</span><span class="mt-0.5 block text-[10px] text-[#a8b7c5]">{{ ucfirst(Auth::user()->role ?? 'engineer') }}</span></span>
                    <form x-show="!sidebarCollapsed" x-cloak method="POST" action="{{ route('logout') }}">@csrf<button class="rounded p-1.5 text-[#a8b7c5] hover:bg-white/10 hover:text-white" aria-label="Keluar" title="Keluar"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></button></form>
                    <form x-show="sidebarCollapsed" x-cloak method="POST" action="{{ route('logout') }}" class="hidden lg:block">@csrf<button class="rounded p-1.5 text-[#a8b7c5] hover:bg-white/10 hover:text-white" aria-label="Keluar" title="Keluar"><svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M10 17l5-5-5-5M15 12H3M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg></button></form>
                </div>
            </div>
        </aside>

        <div x-cloak x-show="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-[#142437]/40 lg:hidden"></div>
        <main class="helpdesk-content min-w-0 transition-[padding] duration-200" x-data="{ serverHealth: 'checking', healthUrl: @js(route('system.health')), async checkServerHealth() { try { const response = await fetch(this.healthUrl, { headers: { Accept: 'application/json' }, cache: 'no-store' }); const contentType = response.headers.get('content-type') || ''; if (response.status >= 500 || ! contentType.includes('application/json')) { this.serverHealth = 'offline'; return; } const health = await response.json(); this.serverHealth = response.ok && health.database === 'online' ? 'online' : 'database-offline'; } catch { this.serverHealth = 'offline'; } }, init() { this.checkServerHealth(); window.setInterval(() => { if (! document.hidden) { this.checkServerHealth(); } }, 30000); } }" :class="sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-[248px]'">
            <header class="sticky top-0 z-20 flex h-[58px] items-center justify-between border-b border-[#dfe5eb] bg-white px-4 sm:px-6">
                <div class="flex min-w-0 items-center gap-2.5"><button @click="sidebarOpen = true" class="rounded p-1.5 text-[#566575] hover:bg-[#f0f3f6] lg:hidden" aria-label="Buka navigasi"><svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 6h16M4 12h16M4 18h16"/></svg></button><div class="min-w-0">{{ $header ?? 'HelpDesk' }}</div></div>
                <div class="ml-3 flex shrink-0 items-center gap-3">
                    <div x-data="{ notificationsOpen: false }" @click.outside="notificationsOpen = false" class="relative">
                        <button type="button" @click="notificationsOpen = ! notificationsOpen" class="relative flex h-8 w-8 items-center justify-center rounded text-[#617181] hover:bg-[#f2f5f7] hover:text-[#263442]" aria-label="Notifikasi" :aria-expanded="notificationsOpen.toString()">
                            <svg class="h-[17px] w-[17px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/></svg>
                            @if ($unreadNotificationCount > 0)<span class="absolute right-0.5 top-0.5 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-[#dc2626] px-1 text-[9px] font-semibold leading-none text-white">{{ $unreadNotificationCount > 9 ? '9+' : $unreadNotificationCount }}</span>@endif
                        </button>
                        <div x-cloak x-show="notificationsOpen" x-transition class="absolute right-0 top-10 z-50 w-[min(340px,calc(100vw-2rem))] overflow-hidden border border-[#dfe5eb] bg-white shadow-lg">
                            <div class="flex items-center justify-between border-b border-[#e8edf1] px-3.5 py-3"><h2 class="text-[11px] font-semibold text-[#2e3d4b]">Notifikasi</h2><span class="text-[9px] text-[#84909c]">{{ $unreadNotificationCount }} belum dibaca</span></div>
                            @forelse ($latestNotifications as $notification)
                                <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="border-b border-[#edf0f3] last:border-b-0">
                                    @csrf
                                    <button type="submit" class="block w-full px-3.5 py-3 text-left hover:bg-[#f8fafc] {{ $notification->read_at === null ? 'bg-[#f5f8fb]' : '' }}">
                                        <span class="flex items-start gap-2"><span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full {{ $notification->read_at === null ? 'bg-[#2563eb]' : 'bg-transparent' }}"></span><span class="min-w-0"><span class="block text-[10px] font-semibold text-[#354555]">{{ $notification->data['title'] ?? 'Notifikasi tiket' }}</span><span class="mt-1 block line-clamp-2 text-[9px] leading-4 text-[#778594]">{{ $notification->data['message'] ?? '' }}</span><span class="mt-1 block text-[9px] text-[#9aa5af]">{{ $notification->created_at->diffForHumans() }}</span></span></span>
                                    </button>
                                </form>
                            @empty
                                <p class="px-3.5 py-6 text-center text-[10px] text-[#84909c]">Belum ada notifikasi.</p>
                            @endforelse
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 text-[10px] text-[#718091]" role="status" aria-live="polite"><span class="h-1.5 w-1.5 rounded-full" :class="serverHealth === 'online' ? 'bg-[#16a34a]' : serverHealth === 'checking' ? 'bg-[#d97706]' : 'bg-[#dc2626]'"></span><span class="hidden sm:inline" x-text="serverHealth === 'online' ? 'Server & database aktif' : serverHealth === 'database-offline' ? 'Database terputus' : serverHealth === 'offline' ? 'Server tidak terjangkau' : 'Memeriksa layanan…'">Memeriksa layanan…</span><span class="hidden text-[#c4ccd4] sm:inline">|</span><span class="hidden sm:inline">{{ now()->format('d M Y') }}</span></div>
                </div>
            </header>
            <div x-cloak x-show="serverHealth === 'offline' || serverHealth === 'database-offline'" class="mx-4 mt-4 flex items-center justify-between gap-3 border border-[#fecaca] bg-[#fef2f2] px-3 py-2.5 text-[11px] text-[#991b1b] sm:mx-6" role="alert"><span x-text="serverHealth === 'database-offline' ? 'Database tidak merespons. Data mungkin tidak tersimpan sampai koneksi pulih.' : 'Koneksi ke server terputus. Periksa jaringan kantor dan coba lagi.'"></span><button type="button" @click="checkServerHealth()" class="shrink-0 font-semibold underline underline-offset-2">Coba lagi</button></div>
            <div class="mx-auto max-w-[1600px] p-4 sm:px-6 sm:py-5">
                @if (session('success'))<div class="mb-4 flex items-center gap-2 border border-[#cfe0d5] bg-[#eef5f0] px-3 py-2.5 text-[12px] text-[#426b50]"><span class="flex h-4 w-4 items-center justify-center rounded-full bg-[#d7e8dc] text-[10px]">✓</span>{{ session('success') }}</div>@endif
                @if (session('status'))<div class="mb-4 flex items-center gap-2 border border-[#cfe0d5] bg-[#eef5f0] px-3 py-2.5 text-[12px] text-[#426b50]">{{ session('status') }}</div>@endif
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
