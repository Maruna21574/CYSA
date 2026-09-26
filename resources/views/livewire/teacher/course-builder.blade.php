<div class="flex flex-col gap-4">
    <p class="text-sm text-slate-600">
        {{ __('Poradie modulov aj kapitol zmeníte potiahnutím za úchyt. Kapitolu môžete presunúť aj do iného modulu.') }}
    </p>

    <div wire:sort="sortModule" class="flex flex-col gap-4">
        @forelse ($modules as $module)
            <section wire:key="module-{{ $module->id }}" wire:sort:item="{{ $module->id }}" class="rounded-xl border border-slate-200 bg-white shadow-xs">
                <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
                    <button type="button" x-sort:handle class="cursor-grab rounded p-1 text-slate-400 hover:text-slate-600" title="{{ __('Presunúť modul') }}">
                        <x-icon name="drag" class="size-5" />
                        <span class="sr-only">{{ __('Presunúť modul :title', ['title' => $module->title]) }}</span>
                    </button>

                    @if ($editingModuleId === $module->id)
                        <form wire:submit="saveModule" class="flex flex-1 items-start gap-2">
                            <div class="flex-1">
                                <label for="module-title-{{ $module->id }}" class="sr-only">{{ __('Názov modulu') }}</label>
                                <input id="module-title-{{ $module->id }}" type="text" wire:model="editingModuleTitle" maxlength="255"
                                       class="block w-full rounded-lg border border-slate-300 px-3 py-1.5 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
                                @error('editingModuleTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <x-button class="py-1.5">{{ __('Uložiť') }}</x-button>
                            <x-button type="button" variant="secondary" class="py-1.5" wire:click="$set('editingModuleId', null)">{{ __('Zrušiť') }}</x-button>
                        </form>
                    @else
                        <h2 class="flex-1 font-semibold text-slate-900">{{ $module->title }}</h2>
                        <button type="button" wire:click="editModule({{ $module->id }})" class="rounded p-1 text-slate-500 hover:text-slate-800" title="{{ __('Premenovať') }}">
                            <x-icon name="pencil" class="size-4" /><span class="sr-only">{{ __('Premenovať modul :title', ['title' => $module->title]) }}</span>
                        </button>
                        <button type="button" wire:click="deleteModule({{ $module->id }})" wire:confirm="{{ __('Odstrániť modul :title?', ['title' => $module->title]) }}" class="rounded p-1 text-slate-500 hover:text-red-700" title="{{ __('Odstrániť') }}">
                            <x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Odstrániť modul :title', ['title' => $module->title]) }}</span>
                        </button>
                    @endif
                </header>

                <ol wire:sort="sortChapter" wire:sort:group="chapters" wire:sort:group-id="{{ $module->id }}" class="flex min-h-12 flex-col divide-y divide-slate-100">
                    @foreach ($module->chapters as $chapter)
                        <li wire:key="chapter-{{ $chapter->id }}" wire:sort:item="{{ $chapter->id }}" class="flex items-center gap-3 px-4 py-2.5">
                            <button type="button" x-sort:handle class="cursor-grab rounded p-1 text-slate-300 hover:text-slate-500" title="{{ __('Presunúť kapitolu') }}">
                                <x-icon name="drag" class="size-4" />
                                <span class="sr-only">{{ __('Presunúť kapitolu :title', ['title' => $chapter->title]) }}</span>
                            </button>
                            <a href="{{ route('teacher.courses.chapters.edit', [$course, $chapter]) }}" class="flex-1 text-sm font-medium text-slate-800 hover:text-indigo-700 hover:underline">
                                {{ $chapter->title }}
                            </a>
                            <span class="hidden items-center gap-2 text-xs text-slate-500 sm:flex">
                                @unless ($chapter->is_published)
                                    <x-badge color="amber">{{ __('Skrytá') }}</x-badge>
                                @endunless
                                @if ($chapter->requires_previous)
                                    <span title="{{ __('Vyžaduje dokončenie predchádzajúcej kapitoly') }}"><x-icon name="lock" class="size-4" /></span>
                                @endif
                                {{ trans_choice('{0} bez materiálov|{1} :count materiál|[2,4] :count materiály|[5,*] :count materiálov', $chapter->materials_count, ['count' => $chapter->materials_count]) }}
                            </span>
                            <a href="{{ route('chapters.show', [$course, $chapter]) }}" class="rounded p-1 text-slate-500 hover:text-slate-800" title="{{ __('Náhľad') }}">
                                <x-icon name="eye" class="size-4" /><span class="sr-only">{{ __('Náhľad kapitoly :title', ['title' => $chapter->title]) }}</span>
                            </a>
                            <button type="button" wire:click="deleteChapter({{ $chapter->id }})" wire:confirm="{{ __('Odstrániť kapitolu :title?', ['title' => $chapter->title]) }}" class="rounded p-1 text-slate-500 hover:text-red-700">
                                <x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Odstrániť kapitolu :title', ['title' => $chapter->title]) }}</span>
                            </button>
                        </li>
                    @endforeach
                </ol>

                <footer class="border-t border-slate-100 px-4 py-2">
                    <a href="{{ route('teacher.courses.chapters.create', [$course, 'module' => $module->id]) }}" class="inline-flex items-center gap-1 text-sm font-medium text-indigo-700 hover:underline">
                        <x-icon name="plus" class="size-4" />{{ __('Pridať kapitolu') }}
                    </a>
                </footer>
            </section>
        @empty
            <x-empty-state icon="squares" :title="__('Kurz zatiaľ nemá žiadne moduly')" :description="__('Začnite pridaním prvého modulu.')" />
        @endforelse
    </div>

    <form wire:submit="addModule" class="flex flex-col gap-2 rounded-xl border border-dashed border-slate-300 bg-white p-4 sm:flex-row sm:items-start">
        <div class="flex-1">
            <label for="new-module" class="sr-only">{{ __('Názov nového modulu') }}</label>
            <input id="new-module" type="text" wire:model="newModuleTitle" maxlength="255" placeholder="{{ __('Názov nového modulu, napr. Bezpečné heslá') }}"
                   class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-600/30">
            @error('newModuleTitle') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
        <x-button><x-icon name="plus" class="size-4" />{{ __('Pridať modul') }}</x-button>
    </form>
</div>
