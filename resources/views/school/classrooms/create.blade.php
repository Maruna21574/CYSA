<x-layouts::app :title="__('Nová trieda')">
    <x-page-header :title="__('Nová trieda')" :breadcrumbs="[
        ['label' => __('Triedy'), 'url' => route('school.classrooms.index')],
        ['label' => __('Nová trieda')],
    ]" />

    <form method="POST" action="{{ route('school.classrooms.store') }}" class="max-w-2xl">
        @include('school.classrooms._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Vytvoriť triedu') }}</x-button>
            <x-link-button variant="secondary" :href="route('school.classrooms.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>
</x-layouts::app>
