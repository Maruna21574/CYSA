@props(['name', 'label', 'value' => null, 'hint' => null])

{{-- Trix editor bound to a hidden input. The HTML is sanitized on the server (Chapter::content). --}}
@once
    @push('head')
        @vite('resources/js/editor.js')
    @endpush
@endonce

<div>
    <label for="{{ $name }}-editor" class="block text-sm font-medium text-slate-700">{{ $label }}</label>
    @if ($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    <input id="{{ $name }}-input" type="hidden" name="{{ $name }}" value="{{ old($name, $value) }}">
    <trix-editor id="{{ $name }}-editor" input="{{ $name }}-input" class="prose-content mt-1 text-sm" aria-label="{{ $label }}"></trix-editor>

    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
