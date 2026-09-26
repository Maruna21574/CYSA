{{-- One study material inside a chapter. --}}
@php $type = $material->type; @endphp

<div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
    <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-slate-900">
        <span class="text-slate-500"><x-icon :name="$type->icon()" class="size-4" /></span>
        {{ $material->title }}
    </h3>

    @switch($type)
        @case(\App\Enums\MaterialType::YouTube)
            <div class="aspect-video overflow-hidden rounded-lg bg-black">
                <iframe class="size-full" src="https://www.youtube-nocookie.com/embed/{{ $material->youtubeId() }}" title="{{ $material->title }}"
                        loading="lazy" allow="accelerometer; encrypted-media; gyroscope; picture-in-picture" allowfullscreen referrerpolicy="strict-origin-when-cross-origin"></iframe>
            </div>
            @break

        @case(\App\Enums\MaterialType::Video)
            <video controls preload="metadata" class="w-full rounded-lg bg-black">
                <source src="{{ route('materials.show', $material) }}" type="{{ $material->mime_type }}">
            </video>
            @break

        @case(\App\Enums\MaterialType::Audio)
            <audio controls preload="metadata" class="w-full">
                <source src="{{ route('materials.show', $material) }}" type="{{ $material->mime_type }}">
            </audio>
            @break

        @case(\App\Enums\MaterialType::Image)
            <img src="{{ route('materials.show', $material) }}" alt="{{ $material->title }}" loading="lazy" class="max-h-[32rem] rounded-lg">
            @break

        @case(\App\Enums\MaterialType::Link)
            <a href="{{ $material->url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 break-all text-sm font-medium text-indigo-700 hover:underline">
                {{ $material->url }} <x-icon name="chevron-right" class="size-4" />
            </a>
            @break

        @default
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm text-slate-600">{{ $material->original_name }} @if ($material->humanSize()) · {{ $material->humanSize() }} @endif</span>
                @if ($material->isInline())
                    <x-link-button variant="secondary" :href="route('materials.show', $material)" target="_blank" rel="noopener">
                        <x-icon name="eye" class="size-4" />{{ __('Otvoriť') }}
                    </x-link-button>
                @endif
                <x-link-button variant="secondary" :href="route('materials.show', [$material, 'download' => 1])">
                    <x-icon name="download" class="size-4" />{{ __('Stiahnuť') }}
                </x-link-button>
            </div>
    @endswitch
</div>
