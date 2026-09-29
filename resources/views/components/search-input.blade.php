@props(['label' => __('Hľadať'), 'placeholder' => null])

<div class="w-full sm:max-w-xs">
    <label for="{{ $attributes->get('id', 'search') }}" class="sr-only">{{ $label }}</label>
    <input
        id="{{ $attributes->get('id', 'search') }}"
        type="search"
        placeholder="{{ $placeholder ?? $label }}"
        {{ $attributes->except('id')->class('block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-xs placeholder:text-slate-400 focus:border-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-600/30') }}
    >
</div>
