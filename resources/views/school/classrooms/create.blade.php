<x-layouts::app :title="\App\Support\Terms::of('Nová trieda')">
    <x-page-header :title="\App\Support\Terms::of('Nová trieda')" :breadcrumbs="[
        ['label' => \App\Support\Terms::of('Triedy'), 'url' => route('school.classrooms.index')],
        ['label' => \App\Support\Terms::of('Nová trieda')],
    ]" />

    <form method="POST" action="{{ route('school.classrooms.store') }}" class="max-w-2xl">
        @include('school.classrooms._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ \App\Support\Terms::of('Vytvoriť triedu') }}</x-button>
            <x-link-button variant="secondary" :href="route('school.classrooms.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
