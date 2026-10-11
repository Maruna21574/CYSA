{{-- One study material inside a chapter. --}}
@php
    $type = $material->type;
    $materialProgress ??= null;
    $isPreview ??= false;
@endphp

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
            <a href="{{ $material->url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1 break-all text-sm font-medium text-brand-700 hover:underline">
                {{ $material->url }} <x-icon name="chevron-right" class="size-4" />
            </a>
            @break

        @default
            @if ($material->isPdf())
                @php
                    $pageCount = $material->pageCount();
                    $readPage = (int) ($materialProgress?->current_page ?? 0);
                    $startingPage = min($pageCount, max(1, $readPage < $pageCount ? $readPage + 1 : $pageCount));
                @endphp

                <div
                    x-data="{
                        currentPage: {{ $startingPage }},
                        totalPages: {{ $pageCount }},
                        completed: @js((bool) $materialProgress?->completed_at),
                        preview: @js($isPreview),
                        saving: false,
                        error: '',
                        get pageUrl() {
                            // The built-in PDF viewer reads these options only when it loads, so the frame is rebuilt per page (the file comes from the browser cache).
                            return @js(route('materials.show', $material)).replace(/#.*$/, '') + '#page=' + this.currentPage + '&navpanes=0&view=FitH';
                        },
                        async markCurrentPage() {
                            if (this.preview || this.completed || this.saving) {
                                return true;
                            }

                            this.saving = true;
                            this.error = '';

                            try {
                                const response = await fetch(@js(route('materials.progress', $material)), {
                                    method: 'POST',
                                    headers: {
                                        'Accept': 'application/json',
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    },
                                    body: JSON.stringify({ page: this.currentPage }),
                                });

                                if (! response.ok) {
                                    throw new Error();
                                }

                                const data = await response.json();
                                this.completed = data.completed;

                                return true;
                            } catch (error) {
                                this.error = @js(__('Nepodarilo sa uložiť priebeh čítania. Skús to znova.'));

                                return false;
                            } finally {
                                this.saving = false;
                            }
                        },
                        async nextPage() {
                            if (await this.markCurrentPage() && this.currentPage < this.totalPages) {
                                this.currentPage++;
                            }
                        },
                    }"
                >
                    <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-100">
                        <template x-for="page in [currentPage]" :key="page">
                            <iframe :src="pageUrl" title="{{ $material->title }}" class="h-[42rem] w-full"></iframe>
                        </template>
                    </div>

                    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                        <button type="button" @click="currentPage = Math.max(1, currentPage - 1)" :disabled="currentPage === 1 || saving"
                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                            {{ __('Predchádzajúca strana') }}
                        </button>
                        <span class="text-sm font-medium tabular-nums text-slate-600">
                            {{ __('Strana') }} <span x-text="currentPage"></span> / {{ $pageCount }}
                        </span>
                        <button type="button" x-show="currentPage < totalPages" @click="nextPage()" :disabled="saving"
                                class="rounded-lg bg-brand-600 px-3 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:cursor-wait disabled:opacity-50">
                            {{ __('Ďalšia strana') }}
                        </button>
                        <button type="button" x-show="currentPage === totalPages && ! completed" @click="markCurrentPage()" :disabled="saving"
                                class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:cursor-wait disabled:opacity-50">
                            {{ __('Dokončiť čítanie') }}
                        </button>
                    </div>

                    <p x-show="completed" x-cloak class="mt-3 rounded-lg bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-800" role="status">
                        {{ __('PDF je prečítané celé.') }}
                    </p>
                    <p x-show="error" x-text="error" class="mt-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700" role="alert"></p>
                    <p x-show="! completed && ! error" class="mt-3 text-xs text-slate-500">
                        {{ $isPreview ? \App\Support\Terms::of('Náhľad PDF pre učiteľa.') : __('Prejdi každú stranu pomocou tlačidla Ďalšia strana. Kapitolu bude možné dokončiť až po prečítaní celého PDF.') }}
                    </p>
                </div>
            @else
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
            @endif
            @break
    @endswitch
</div>
