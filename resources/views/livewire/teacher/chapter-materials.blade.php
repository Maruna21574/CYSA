<div class="flex flex-col gap-4">
    @if ($materials->isEmpty())
        <p class="text-sm text-slate-500">{{ __('Kapitola zatiaľ nemá žiadne materiály.') }}</p>
    @else
        <ul wire:sort="sortMaterial" class="flex flex-col divide-y divide-slate-100 rounded-lg border border-slate-200">
            @foreach ($materials as $material)
                <li wire:key="material-{{ $material->id }}" wire:sort:item="{{ $material->id }}" class="flex items-center gap-3 bg-white px-3 py-2">
                    <button type="button" x-sort:handle class="cursor-grab text-slate-300 hover:text-slate-500">
                        <x-icon name="drag" class="size-4" /><span class="sr-only">{{ __('Presunúť :title', ['title' => $material->title]) }}</span>
                    </button>
                    <span class="text-slate-500"><x-icon :name="$material->type->icon()" /></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-slate-900">{{ $material->title }}</span>
                        <span class="text-xs text-slate-500">
                            {{ $material->type->label() }}
                            @if ($material->humanSize()) · {{ $material->humanSize() }} @endif
                            @if ($material->url) · <span class="break-all">{{ \Illuminate\Support\Str::limit($material->url, 60) }}</span> @endif
                        </span>
                    </span>
                    @if ($material->isFile())
                        <a href="{{ route('materials.show', $material) }}" target="_blank" rel="noopener" class="rounded p-1 text-slate-500 hover:text-slate-800">
                            <x-icon name="eye" class="size-4" /><span class="sr-only">{{ __('Otvoriť :title', ['title' => $material->title]) }}</span>
                        </a>
                    @endif
                    <button type="button" wire:click="delete({{ $material->id }})" wire:confirm="{{ __('Odstrániť materiál :title?', ['title' => $material->title]) }}" class="rounded p-1 text-slate-500 hover:text-red-700">
                        <x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Odstrániť :title', ['title' => $material->title]) }}</span>
                    </button>
                </li>
            @endforeach
        </ul>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <form wire:submit="saveUpload" class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4"
              x-data="{ progress: 0, uploading: false }"
              x-on:livewire-upload-start="uploading = true"
              x-on:livewire-upload-finish="uploading = false"
              x-on:livewire-upload-error="uploading = false"
              x-on:livewire-upload-progress="progress = $event.detail.progress">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="upload" class="size-4" />{{ __('Nahrať súbor') }}</h3>

            <div>
                <label for="material-upload" class="sr-only">{{ __('Súbor') }}</label>
                <input id="material-upload" type="file" wire:model="upload"
                       class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-sm file:font-semibold file:text-indigo-700"
                       aria-describedby="material-upload-hint">
                <p id="material-upload-hint" class="mt-1 text-xs text-slate-500">{{ __('Povolené: :types. Najviac :size MB.', ['types' => $extensions, 'size' => $maxUploadMb]) }}</p>
                @error('upload') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div x-show="uploading" x-cloak class="h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" :aria-valuenow="progress" aria-valuemin="0" aria-valuemax="100">
                <div class="h-full bg-indigo-600 transition-all" :style="`width: ${progress}%`"></div>
            </div>

            <x-form.input name="uploadTitle" id="upload-title" :label="__('Názov (nepovinné)')" wire:model="uploadTitle" maxlength="255" />

            <x-button x-bind:disabled="uploading" wire:loading.attr="disabled" wire:target="upload,saveUpload">{{ __('Uložiť súbor') }}</x-button>
        </form>

        <form wire:submit="saveLink" class="flex flex-col gap-3 rounded-lg border border-slate-200 p-4">
            <h3 class="flex items-center gap-2 text-sm font-semibold text-slate-900"><x-icon name="link" class="size-4" />{{ __('Pridať odkaz alebo YouTube video') }}</h3>

            <x-form.input name="linkUrl" id="link-url" type="url" :label="__('Adresa')" wire:model="linkUrl" placeholder="https://" maxlength="2048"
                :hint="__('Odkaz na YouTube sa automaticky zobrazí ako prehrávač.')" />
            <x-form.input name="linkTitle" id="link-title" :label="__('Názov (nepovinné)')" wire:model="linkTitle" maxlength="255" />

            <x-button>{{ __('Pridať') }}</x-button>
        </form>
    </div>
</div>
