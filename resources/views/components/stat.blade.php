@props(['label', 'value', 'hint' => null, 'icon' => null, 'color' => 'slate', 'href' => null])

@php
    $colors = [
        'slate' => ['card' => 'border-slate-200 bg-white', 'icon' => 'bg-slate-100 text-slate-600'],
        'blue' => ['card' => 'border-blue-200 bg-blue-50/70', 'icon' => 'bg-blue-100 text-blue-700'],
        'violet' => ['card' => 'border-violet-200 bg-violet-50/70', 'icon' => 'bg-violet-100 text-violet-700'],
        'emerald' => ['card' => 'border-emerald-200 bg-emerald-50/70', 'icon' => 'bg-emerald-100 text-emerald-700'],
        'amber' => ['card' => 'border-amber-200 bg-amber-50/70', 'icon' => 'bg-amber-100 text-amber-700'],
        'rose' => ['card' => 'border-rose-200 bg-rose-50/70', 'icon' => 'bg-rose-100 text-rose-700'],
    ];
    $theme = $colors[$color] ?? $colors['slate'];
@endphp

@if ($href)
<a href="{{ $href }}" {{ $attributes->class(['block rounded-xl border p-4 shadow-xs transition hover:border-brand-300 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600', $theme['card']]) }}>
@else
<div {{ $attributes->class(['rounded-xl border p-4 shadow-xs', $theme['card']]) }}>
@endif
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
        @if ($icon)
            <span class="rounded-lg p-2 {{ $theme['icon'] }}"><x-icon :name="$icon" class="size-5" /></span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
@if ($href)
</a>
@else
</div>
@endif
