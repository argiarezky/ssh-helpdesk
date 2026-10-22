@props(['status'])
@php($styles = ['open' => 'bg-[#f1f5f9] text-[#64748B]', 'pending' => 'bg-[#fff7ed] text-[#D97706]', 'in_progress' => 'bg-[#eff6ff] text-[#2563EB]', 'resolved' => 'bg-[#f0fdf4] text-[#16A34A]', 'closed' => 'bg-[#f1f5f9] text-[#475569]'])
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded px-2 py-1 text-[9px] font-medium '.($styles[$status] ?? $styles['closed'])]) }}>{{ ['in_progress' => 'In progress'][$status] ?? ucfirst($status) }}</span>
