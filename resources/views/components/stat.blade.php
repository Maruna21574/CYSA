@props(['label', 'value', 'hint' => null, 'icon' => null])

<div {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-4 shadow-xs') }}>
    <div class="flex items-center justify-between gap-2">
        <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
        @if ($icon)
            <span class="text-slate-400"><x-icon :name="$icon" class="size-5" /></span>
        @endif
    </div>
    <p class="mt-2 text-2xl font-semibold tabular-nums text-slate-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>
