@props(['name', 'label', 'value' => null, 'hint' => null, 'rows' => 4])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $isLivewire = (bool) $attributes->whereStartsWith('wire:model')->getAttributes();
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>

    <textarea
        id="{{ $id }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('id')->class([
            'mt-1 block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs focus:outline-none focus:ring-2 focus:ring-brand-600/30',
            'border-red-400' => $hasError,
            'border-slate-300 focus:border-brand-600' => ! $hasError,
        ]) }}
    >{{ $isLivewire ? '' : old($name, $value) }}</textarea>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
