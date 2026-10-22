<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center rounded border border-transparent bg-[#25577e] px-3 py-2 text-[10px] font-semibold text-white transition hover:bg-[#1e496b] focus:outline-none focus:ring-2 focus:ring-[#d7e5ef] focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
