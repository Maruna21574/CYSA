<x-layouts::app :title="$classroom->name">
    <x-page-header :title="__('Upraviť triedu :name', ['name' => $classroom->name])" :breadcrumbs="[
        ['label' => __('Triedy'), 'url' => route('school.classrooms.index')],
        ['label' => $classroom->name, 'url' => route('school.classrooms.show', $classroom)],
        ['label' => __('Upraviť')],
    ]" />

    <form method="POST" action="{{ route('school.classrooms.update', $classroom) }}" class="max-w-2xl">
        @method('PUT')
        @include('school.classrooms._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť') }}</x-button>
            <x-link-button variant="secondary" :href="route('school.classrooms.show', $classroom)">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    <x-card :title="__('Odstránenie triedy')" class="mt-8 max-w-2xl border-red-200">
        <p class="text-sm text-slate-600">{{ __('Účty študentov a učiteľov zostanú zachované, zruší sa iba trieda.') }}</p>
        <form method="POST" action="{{ route('school.classrooms.destroy', $classroom) }}" class="mt-3"
              x-data @submit="if (! confirm(@js(__('Naozaj odstrániť túto triedu?')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <x-button variant="danger">{{ __('Odstrániť triedu') }}</x-button>
        </form>
    </x-card>
</x-layouts::app>
