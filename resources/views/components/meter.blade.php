@props(['value' => null, 'label' => null, 'tone' => 'score'])

{{-- Horizontal bar for a percentage 0-100. tone="score": colour by level (red < 50 <= amber < 75 <= green); tone="progress": neutral colour, progress is never "bad". --}}
@php
    $percent = $value === null ? null : max(0, min(100, (float) $value));
    $color = match (true) {
        $percent === null => 'bg-slate-300',
        $tone === 'progress' => $percent >= 100 ? 'bg-emerald-500' : 'bg-indigo-500',
        $percent < 50 => 'bg-red-500',
        $percent < 75 => 'bg-amber-500',
        default => 'bg-emerald-500',
    };
@endphp

<div {{ $attributes->class('flex items-center gap-3') }}>
    <div class="h-2 flex-1 overflow-hidden rounded-full bg-slate-100" role="meter" aria-valuemin="0" aria-valuemax="100"
         @if ($percent !== null) aria-valuenow="{{ round($percent) }}" @endif @if ($label) aria-label="{{ $label }}" @endif>
        <div class="h-full rounded-full {{ $color }}" style="width: {{ $percent ?? 0 }}%"></div>
    </div>
    <span class="w-12 shrink-0 text-right text-sm tabular-nums text-slate-700">{{ $percent === null ? '—' : rtrim(rtrim(number_format($percent, 1, ',', ''), '0'), ',').' %' }}</span>
</div>
