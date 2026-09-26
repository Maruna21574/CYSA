@props(['title' => null])

<section {{ $attributes->class('rounded-xl border border-slate-200 bg-white p-5 shadow-xs') }}>
    @if ($title)
        <h2 class="mb-3 text-base font-semibold text-slate-900">{{ $title }}</h2>
    @endif

    {{ $slot }}
</section>
