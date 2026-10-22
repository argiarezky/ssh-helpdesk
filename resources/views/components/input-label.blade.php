@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-[11px] font-medium text-[#536271]']) }}>
    {{ $value ?? $slot }}
</label>
