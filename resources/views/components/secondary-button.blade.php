<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded border border-[#d8e0e7] bg-white px-3 py-2 text-[10px] font-semibold text-[#536271] transition hover:bg-[#f5f7f9] focus:outline-none focus:ring-2 focus:ring-[#d7e5ef] focus:ring-offset-2 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
