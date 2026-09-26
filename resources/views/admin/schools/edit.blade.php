<x-layouts::app :title="$school->name">
    <x-page-header :title="$school->name" :breadcrumbs="[
        ['label' => __('Školy'), 'url' => route('admin.schools.index')],
        ['label' => $school->name],
    ]" />

    <form method="POST" action="{{ route('admin.schools.update', $school) }}" class="max-w-2xl">
        @method('PUT')
        @include('admin.schools._form')

        <div class="mt-4 flex gap-2">
            <x-button>{{ __('Uložiť') }}</x-button>
            <x-link-button variant="secondary" :href="route('admin.schools.index')">{{ __('Zrušiť') }}</x-link-button>
        </div>
    </form>

    <x-card :title="__('Odstránenie školy')" class="mt-8 max-w-2xl border-red-200">
        <p class="text-sm text-slate-600">{{ __('Odstrániť možno iba školu bez používateľov a tried. Inak školu deaktivujte.') }}</p>
        <form method="POST" action="{{ route('admin.schools.destroy', $school) }}" class="mt-3"
              x-data @submit="if (! confirm(@js(__('Naozaj odstrániť túto školu?')))) $event.preventDefault()">
            @csrf
            @method('DELETE')
            <x-button variant="danger">{{ __('Odstrániť školu') }}</x-button>
        </form>
    </x-card>
</x-layouts::app>
