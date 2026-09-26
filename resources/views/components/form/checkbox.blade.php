@props(['name', 'label', 'hint' => null, 'checked' => false])

@php $id = $attributes->get('id', $name); @endphp

<div class="flex items-start gap-3">
    {{-- Hidden input so an unchecked box submits "0" in classic forms. --}}
    <input type="hidden" name="{{ $name }}" value="0">
    <input
        id="{{ $id }}"
        name="{{ $name }}"
        type="checkbox"
        value="1"
        @checked(old($name, $checked))
        @if ($hint) aria-describedby="{{ $id }}-hint" @endif
        {{ $attributes->except('id')->class('mt-0.5 size-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600') }}
    >
    <div>
        <label for="{{ $id }}" class="text-sm font-medium text-slate-700">{{ $label }}</label>
        @if ($hint)
            <p id="{{ $id }}-hint" class="text-xs text-slate-500">{{ $hint }}</p>
        @endif
        @error($name)
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
