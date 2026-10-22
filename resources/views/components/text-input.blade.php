@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded border-[#d8e0e7] bg-white text-[11px] shadow-none focus:border-[#39739d] focus:ring-[#d7e5ef]']) }}>
