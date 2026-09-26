<x-layouts::app :title="__('Oznámenia kurzu')">
    @include('teacher.courses._header', ['active' => 'announcements'])

    <div class="grid gap-6 lg:grid-cols-5">
        <form method="POST" action="{{ route('teacher.courses.announcements.store', $course) }}" class="lg:col-span-2">
            @csrf
            <x-card :title="__('Nové oznámenie')" class="flex flex-col gap-4">
                <x-form.input name="title" :label="__('Nadpis')" required maxlength="255" />
                <x-form.textarea name="body" :label="__('Text')" rows="6" required maxlength="5000" />
                <p class="text-xs text-slate-500">{{ __('Oznámenie dostanú všetci študenti, ktorým je kurz priradený, ako notifikáciu.') }}</p>
                <div><x-button>{{ __('Odoslať oznámenie') }}</x-button></div>
            </x-card>
        </form>

        <div class="flex flex-col gap-4 lg:col-span-3">
            @forelse ($announcements as $announcement)
                <x-card>
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold text-slate-900">{{ $announcement->title }}</h2>
                            <p class="text-xs text-slate-500">{{ $announcement->author?->name }} · {{ $announcement->created_at->translatedFormat('j. n. Y H:i') }}</p>
                        </div>
                        <form method="POST" action="{{ route('teacher.courses.announcements.destroy', [$course, $announcement]) }}"
                              x-data @submit="if (! confirm(@js(__('Odstrániť oznámenie?')))) $event.preventDefault()">
                            @csrf
                            @method('DELETE')
                            <button class="rounded p-1 text-slate-400 hover:text-red-700"><x-icon name="trash" class="size-4" /><span class="sr-only">{{ __('Odstrániť') }}</span></button>
                        </form>
                    </div>
                    <p class="mt-2 text-sm whitespace-pre-line text-slate-700">{{ $announcement->body }}</p>
                </x-card>
            @empty
                <x-empty-state icon="bell" :title="__('Zatiaľ žiadne oznámenia')" />
            @endforelse
        </div>
    </div>
</x-layouts::app>
