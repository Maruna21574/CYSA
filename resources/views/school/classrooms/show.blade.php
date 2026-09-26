<x-layouts::app :title="$classroom->name">
    <x-page-header
        :title="$classroom->name"
        :description="__('Školský rok :year', ['year' => $classroom->school_year])"
        :breadcrumbs="[
            ['label' => __('Triedy'), 'url' => route('school.classrooms.index')],
            ['label' => $classroom->name],
        ]"
    >
        <x-slot:actions>
            @can('update', $classroom)
                <x-link-button variant="secondary" :href="route('school.classrooms.edit', $classroom)">{{ __('Upraviť triedu') }}</x-link-button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    <livewire:classrooms.classroom-members :classroom="$classroom" />
</x-layouts::app>
