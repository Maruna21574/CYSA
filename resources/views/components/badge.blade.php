@props(['color' => 'slate'])

@php
    $colors = [
        'slate' => 'bg-slate-100 text-slate-700 ring-slate-200',
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'red' => 'bg-red-50 text-red-700 ring-red-200',
        'amber' => 'bg-amber-50 text-amber-800 ring-amber-200',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset', $colors[$color] ?? $colors['slate']]) }}>
    {{ $slot }}
</span>
