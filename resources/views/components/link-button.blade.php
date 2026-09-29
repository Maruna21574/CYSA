@props(['variant' => 'primary', 'href'])

@php
    $variants = [
        'primary' => 'bg-brand-600 text-white hover:bg-accent',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50',
    ];
@endphp

<a href="{{ $href }}" {{ $attributes->class([
    'inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold shadow-xs transition',
    'focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600',
    $variants[$variant] ?? $variants['primary'],
]) }}>
    {{ $slot }}
</a>
