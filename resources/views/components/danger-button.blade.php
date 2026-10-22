<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded-md border border-transparent bg-[#dc2626] px-3.5 py-2 text-[10px] font-semibold text-white transition hover:bg-[#b91c1c] focus:outline-none focus:ring-2 focus:ring-[#ef4444] focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
