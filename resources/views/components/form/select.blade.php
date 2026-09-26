@props(['name', 'label', 'options' => [], 'placeholder' => null, 'hint' => null, 'selected' => null])

{{-- $options: [value => label]. $selected is used for non-Livewire forms (old input wins). --}}
@php
    $id = $attributes->get('id', $name);
    $hasError = $errors->has($name);
    $current = (string) old($name, $selected instanceof \BackedEnum ? $selected->value : $selected);
    $describedBy = collect([$hint ? $id.'-hint' : null, $hasError ? $id.'-error' : null])->filter()->implode(' ');
@endphp

<div>
    <label for="{{ $id }}" class="block text-sm font-medium text-slate-700">{{ $label }}</label>

    <select
        id="{{ $id }}"
        name="{{ $name }}"
        @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
        @if ($hasError) aria-invalid="true" @endif
        {{ $attributes->except('id')->class([
            'mt-1 block w-full rounded-lg border bg-white px-3 py-2 text-sm text-slate-900 shadow-xs',
            'focus:outline-none focus:ring-2 focus:ring-indigo-600/30',
            'border-red-400 focus:border-red-500' => $hasError,
            'border-slate-300 focus:border-indigo-600' => ! $hasError,
        ]) }}
    >
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $value => $optionLabel)
            <option value="{{ $value }}" @selected($current === (string) $value)>{{ $optionLabel }}</option>
        @endforeach
    </select>

    @if ($hint)
        <p id="{{ $id }}-hint" class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @error($name)
        <p id="{{ $id }}-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
