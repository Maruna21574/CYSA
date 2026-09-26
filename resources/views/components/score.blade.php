@props(['attempt'])

{{-- Percentage badge of a finished attempt: green when passed, red otherwise. --}}
<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-sm font-semibold tabular-nums',
    'bg-emerald-50 text-emerald-700' => $attempt->passed,
    'bg-red-50 text-red-700' => ! $attempt->passed,
]) }}>
    {{ rtrim(rtrim(number_format((float) $attempt->percentage, 1, ',', ''), '0'), ',') }} %
</span>
