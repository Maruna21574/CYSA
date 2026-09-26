<x-layouts::app :title="__('Nová kapitola')">
    <x-page-header :title="__('Nová kapitola')" :breadcrumbs="[
        ['label' => __('Kurzy'), 'url' => route('teacher.courses.index')],
        ['label' => $course->title, 'url' => route('teacher.courses.show', $course)],
        ['label' => __('Nová kapitola')],
    ]" />

    <form method="POST" action="{{ route('teacher.courses.chapters.store', $course) }}" class="max-w-4xl">
        @include('teacher.chapters._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Vytvoriť kapitolu') }}</x-button>
            <x-link-button variant="secondary" :href="route('teacher.courses.show', $course)">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
