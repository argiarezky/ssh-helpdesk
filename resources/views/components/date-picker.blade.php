<div>
    <!-- Order your soul. Reduce your wants. - Augustine -->
</div>
@props(['name', 'value' => ''])

<div x-data="datePicker(@js($value))" @click.outside="open = false" @keydown.escape.window="open = false" class="relative w-full">
    <input type="hidden" name="{{ $name }}" :value="value">

    <button type="button" @click="toggle()" :aria-expanded="open.toString()" aria-haspopup="dialog" :aria-label="'Pilih tanggal, ' + displayValue" class="flex h-9 w-full items-center justify-between gap-2 rounded border border-[#e1e6e2] bg-white px-3 text-left text-[11px] text-[#292f2b] shadow-sm transition hover:border-[#276749] focus:border-[#276749] focus:outline-none focus:ring-2 focus:ring-[#276749]/20">
        <span x-text="displayValue"></span>
        <svg class="h-4 w-4 shrink-0 text-[#276749]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7 3v4M17 3v4M3.5 9h17M8 13h.01M12 13h.01M16 13h.01M8 17h.01M12 17h.01" stroke-linecap="round"/></svg>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.left role="dialog" aria-label="Pilih tanggal" class="absolute left-0 top-full z-50 mt-2 w-[min(19rem,calc(100vw-2rem))] rounded-lg border border-[#e1e6e2] bg-white p-3 shadow-xl shadow-[#202923]/15">
        <div class="mb-3 flex items-center justify-between gap-2">
            <button type="button" @click="moveMonth(-1)" aria-label="Bulan sebelumnya" class="flex h-8 w-8 items-center justify-center rounded-md text-[#292f2b] transition hover:bg-[#276749] hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <h2 class="text-[13px] font-semibold capitalize text-[#292f2b]" x-text="monthLabel"></h2>
            <button type="button" @click="moveMonth(1)" aria-label="Bulan berikutnya" class="flex h-8 w-8 items-center justify-center rounded-md text-[#292f2b] transition hover:bg-[#276749] hover:text-white">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center">
            <template x-for="weekday in weekdays" :key="weekday">
                <span class="flex h-7 items-center justify-center text-[10px] font-semibold uppercase text-[#68756c]" x-text="weekday"></span>
            </template>
            <template x-for="(day, index) in calendarDays" :key="index">
                <button type="button" @click="selectDay(day)" :disabled="! day" :aria-pressed="isSelected(day).toString()" :class="{
                    'bg-[#276749] font-semibold text-white': isSelected(day),
                    'font-semibold text-[#276749] ring-1 ring-inset ring-[#276749]': isToday(day) && ! isSelected(day),
                    'text-[#292f2b] hover:bg-[#276749] hover:text-white': day && ! isSelected(day),
                }" class="flex h-8 w-8 items-center justify-center rounded-md text-[11px] transition-colors disabled:pointer-events-none" x-text="day ? day.getDate() : ''"></button>
            </template>
        </div>

        <div class="mt-3 flex items-center justify-between border-t border-[#e1e6e2] pt-3">
            <button type="button" @click="clear()" class="rounded px-2 py-1.5 text-[10px] font-medium text-[#68756c] transition hover:bg-[#202923] hover:text-white">Hapus tanggal</button>
            <button type="button" @click="selectToday()" class="rounded bg-[#276749] px-3 py-1.5 text-[10px] font-semibold text-white transition hover:bg-[#1f5139]">Hari ini</button>
        </div>
    </div>
</div>
