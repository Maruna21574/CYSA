@props(['icon' => 'document', 'title', 'description' => null])

<div {{ $attributes->class('flex flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-white px-6 py-12 text-center') }}>
    <span class="mb-3 rounded-full bg-slate-100 p-3 text-slate-500">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>

    @if ($description)
        <p class="mt-1 max-w-md text-sm text-slate-600">{{ $description }}</p>
    @endif

    @if (! $slot->isEmpty())
        <div class="mt-4">{{ $slot }}</div>
    @endif
</div>
