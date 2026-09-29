@props(['name', 'label', 'type' => 'text', 'hint' => null, 'value' => null])

@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    // Classic forms get old input back; Livewire fields (wire:model) and passwords never do.
    $fillValue = ! in_array($type, ['password', 'file'], true) && ! $attributes->whereStartsWith('wire:model')->getAttributes();
    $describedBy = collect([$hint ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>

    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if ($fillValue) value="{{ old($name, $value) }}" @endif
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('id')->class([
            'mt-1 block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs placeholder:text-slate-400',
            'focus:outline-none focus:ring-2 focus:ring-brand-600/30',
            'border-red-400 focus:border-red-500' => $hasError,
            'border-slate-300 focus:border-brand-600' => ! $hasError,
        ]) }}
    />

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
