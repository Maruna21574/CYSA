<x-layouts::app :title="$chapter->title">
    <x-page-header :title="$chapter->title" :breadcrumbs="[
        ['label' => __('Kurzy'), 'url' => route('teacher.courses.index')],
        ['label' => $course->title, 'url' => route('teacher.courses.show', $course)],
        ['label' => $chapter->title],
    ]">
        <x-slot:actions>
            @if (\App\Services\AI\AIQuizGenerationService::isAvailable() && filled(strip_tags((string) $chapter->content)))
                <x-link-button variant="secondary" :href="route('teacher.ai.create', ['chapter' => $chapter->id])"><x-icon name="sparkles" class="size-4" />{{ __('Otázky z textu (AI)') }}</x-link-button>
            @endif
            <x-link-button variant="secondary" :href="route('chapters.show', [$course, $chapter])"><x-icon name="eye" class="size-4" />{{ __('Náhľad') }}</x-link-button>
        </x-slot:actions>
    </x-page-header>

    <div class="flex max-w-4xl flex-col gap-6">
        <form method="POST" action="{{ route('teacher.courses.chapters.update', [$course, $chapter]) }}">
            @method('PUT')
            @include('teacher.chapters._form')

            <div class="mt-4 flex gap-2">
                <x-button>{{ __('Uložiť kapitolu') }}</x-button>
                <x-link-button variant="secondary" :href="route('teacher.courses.show', $course)">{{ __('Späť na kurz') }}</x-link-button>
            </div>
        </form>

        <x-card :title="__('Materiály kapitoly')">
            <livewire:teacher.chapter-materials :chapter="$chapter" />
        </x-card>

        <x-card :title="__('Odstránenie kapitoly')" class="border-red-200">
            <form method="POST" action="{{ route('teacher.courses.chapters.destroy', [$course, $chapter]) }}"
                  x-data @submit="if (! confirm(@js(__('Naozaj odstrániť túto kapitolu?')))) $event.preventDefault()">
                @csrf
                @method('DELETE')
                <x-button variant="danger">{{ __('Odstrániť kapitolu') }}</x-button>
            </form>
        </x-card>
    </div>
</x-layouts::app>
