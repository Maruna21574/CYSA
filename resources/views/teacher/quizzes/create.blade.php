<x-layouts::app :title="__('Nový test')">
    <x-page-header :title="__('Nový test')" :breadcrumbs="[
        ['label' => __('Testy'), 'url' => route('teacher.quizzes.index')],
        ['label' => __('Nový test')],
    ]" />

    @if ($courses === [])
        <x-empty-state icon="book" :title="__('Najprv vytvorte kurz')" :description="__('Každý test patrí ku kurzu.')">
            <x-link-button :href="route('teacher.courses.create')">{{ __('Vytvoriť kurz') }}</x-link-button>
        </x-empty-state>
    @else
        <form method="POST" action="{{ route('teacher.quizzes.store') }}" class="max-w-3xl">
            @include('teacher.quizzes._form')

            <div class="mt-4 flex gap-2">
                <x-button>{{ __('Vytvoriť test') }}</x-button>
                <x-link-button variant="secondary" :href="route('teacher.quizzes.index')">{{ __('Zrušiť') }}</x-link-button>
            </div>
        </form>
    @endif
</x-layouts::app>
